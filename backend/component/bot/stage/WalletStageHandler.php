<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\models\Pages;
use backend\queue\PaynetQueue;
use common\models\Botuser;
use common\models\Paynet;
use common\models\Payment;
use common\models\Setting;
use common\models\Text;
use Yii;

/**
 * Pages::WALLET_PAGE, WITHDRAW_TYPE_PAGE, WITHDRAW_ACCOUNT_PAGE,
 * WITHDRAW_AMOUNT_PAGE — extracted verbatim from
 * BotController::showWalletPage()/handleWalletPage()/showWithdrawTypePage()/
 * handleWithdrawTypePage()/showWithdrawAccountPage()/handleWithdrawAccountPage()/
 * showWithdrawAmountPage()/handleWithdrawAmountPage()/isPaymentMaintenance()/
 * isAmount()/getUserBalance()/minusBalance().
 */
class WalletStageHandler implements BotStageInterface
{
    public function __construct(
        private MainMenuStageHandler $mainMenu
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::WALLET_PAGE;
        switch ($page) {
            case Pages::WITHDRAW_TYPE_PAGE:
                $this->showWithdrawType($ctx);
                break;
            case Pages::WITHDRAW_ACCOUNT_PAGE:
                $this->showWithdrawAccount($ctx);
                break;
            case Pages::WITHDRAW_AMOUNT_PAGE:
                $this->showWithdrawAmount($ctx);
                break;
            default:
                $this->showWallet($ctx);
        }
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::WITHDRAW_TYPE_PAGE:
                $this->handleWithdrawType($ctx);
                break;
            case Pages::WITHDRAW_ACCOUNT_PAGE:
                $this->handleWithdrawAccount($ctx);
                break;
            case Pages::WITHDRAW_AMOUNT_PAGE:
                $this->handleWithdrawAmount($ctx);
                break;
            case Pages::WALLET_PAGE:
                $this->handleWallet($ctx);
                break;
        }
    }

    public function showWallet(BotContext $ctx): void
    {
        try {

            $ctx->page = Pages::WALLET_PAGE;

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Withdraw")),
                ],
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
                ]
            ];

            $user = Botuser::find()->where(['chat_id' => $ctx->chat_id])->one();

            $balance = $user->balance;

            $text = sprintf($ctx->getMText("Withdraw balance"), $balance);

            $ctx->sendMessageWithKeyborad($text, $option);

        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public function handleWallet(BotContext $ctx): void
    {
        switch ($ctx->getKeywordText($ctx->text)){
            case 'Withdraw':
                if ($this->isPaymentMaintenance($ctx)) return;
                $this->showWithdrawType($ctx);
                break;
            default:
                $this->showWallet($ctx);
        }
    }

    public function showWithdrawType(BotContext $ctx): void
    {
        try {

            $ctx->page = Pages::WITHDRAW_TYPE_PAGE;

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("to phone")),
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("to card")),
                ],
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
                ]
            ];

            $text = $ctx->getMText("Choose Withdraw type");

            $ctx->sendMessageWithKeyborad($text, $option);

        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public function handleWithdrawType(BotContext $ctx): void
    {
        if ($this->isPaymentMaintenance($ctx)) return;
        switch ($ctx->getKeywordText($ctx->text)){
            case 'to phone':
                $ctx->withdrawType = Payment::TO_PHONE;
                $this->showWithdrawAccount($ctx);
                break;
            case 'to card':
                $ctx->withdrawType = Payment::TO_CARD;
                $this->showWithdrawAccount($ctx);
                break;
            default:
                $this->showWithdrawType($ctx);
        }
    }

    public function showWithdrawAccount(BotContext $ctx): void
    {
        try {

            $ctx->page = Pages::WITHDRAW_ACCOUNT_PAGE;

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
                ]
            ];

            switch ($ctx->withdrawType){
                case Payment::TO_PHONE:
                    $text = $ctx->getMText("Enter phone account number");
                    break;
                case Payment::TO_CARD:
                    $text = $ctx->getMText("Enter card account number");
                    break;
            }

            $ctx->sendMessageWithKeyborad($text, $option);

        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public function handleWithdrawAccount(BotContext $ctx): void
    {
        if ($this->isPaymentMaintenance($ctx)) return;
        $phone_pattern = '/^(?:\+998|998)?(90|91|93|94|95|97|98|99|33|88|70|77|87)\d{7}$/';
        $card_pattern = '/^(4062|4067|4073|4097|4198|4294|5440|5555|5614|6262|8600|9860)\d{12}$/';

        $pattern = $ctx->withdrawType == Payment::TO_PHONE ? $phone_pattern : $card_pattern;

        if (preg_match($pattern, $ctx->text)){
            $ctx->withdrawAccaunt = $ctx->text;
            $this->showWithdrawAmount($ctx);
        }else{
            $this->showWithdrawAccount($ctx);
        }
    }

    public function showWithdrawAmount(BotContext $ctx): void
    {
        try {

            $ctx->page = Pages::WITHDRAW_AMOUNT_PAGE;

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
                ]
            ];

            $text = $ctx->getMText("Enter amount");

            $ctx->sendMessageWithKeyborad($text, $option);

        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public function handleWithdrawAmount(BotContext $ctx): void
    {
        if ($this->isPaymentMaintenance($ctx)) return;
        if ($this->isAmount($ctx)){
            if ($this->paymentStatus()){
                if ($ctx->text <= $this->getUserBalance($ctx)){
                    $user = Botuser::findOne(['chat_id' => $ctx->chat_id]);
                    $activePaynet = Paynet::getRandomActive();
                    if (!$activePaynet) {
                        $ctx->sendMessage($ctx->getMText('not working payment'));
                        return;
                    }

                    // User hisobidan pulni yechish
                    $this->minusBalance($ctx);

                    // Payment yaratish
                    $payment = new Payment();
                    $payment->user_id = $user->id;
                    $payment->type = $ctx->withdrawType;
                    $payment->account = $ctx->withdrawAccaunt;
                    $payment->amount = $ctx->text;
                    $payment->save();

                    $text = sprintf($ctx->getMText('Sent payment message'), $payment->id, $payment->account, number_format($payment->amount, 0, '.', ' '));

                    $data = [
                        'user_id' => $user->id,
                        'payment_order_id' => $payment->id,
                        'account_number' => $payment->account,
                        'amount' => $payment->amount,
                        'payment_type' => $payment->type,
                        'paynet_id' => $activePaynet->paynet_id,
                    ];

                    $queue_name = "paynetQueue";
                    Yii::$app->$queue_name->delay(1)->push(new PaynetQueue($data));

                    $this->mainMenu->show($ctx, ['text' => $text]);

                }else{
                    $ctx->sendMessage($ctx->getMText('Not enough funds!'));
                }
            }else{
                $ctx->sendMessage($ctx->getMText('not working payment'));
            }
        }else{
            $this->showWithdrawAmount($ctx);
        }
    }

    /**
     * Shared by the Wallet-button global command (BotCommandRouter) and every
     * withdraw step here — same check, same message, same fallback text as
     * BotController::isPaymentMaintenance() originally had.
     */
    public function isPaymentMaintenance(BotContext $ctx): bool
    {
        if (!Setting::getPaymentStatus()) {
            $lang   = $ctx->lang ?: 'uz';
            $record = Text::findOne(['keyword' => 'payment_maintenance']);
            $msg    = ($record && $record->$lang)
                ? $record->$lang
                : "Hozirda to'lov tizimida texnik ishlar qilinmoqda, iltimos keyinroq urinib ko'ring.";
            $ctx->sendMessage($msg);
            return true;
        }
        return false;
    }

    private function isAmount(BotContext $ctx): bool
    {
        return (bool) preg_match('/^[1-9]\d{3,5}$/', $ctx->text);
    }

    private function paymentStatus(): bool
    {
        return true;
    }

    private function getUserBalance(BotContext $ctx)
    {
        $user = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        return is_null($user) ? 0 : $user->balance;
    }

    private function minusBalance(BotContext $ctx): void
    {
        $user = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        $user->balance -= $ctx->text;
        $user->save(false);
    }
}
