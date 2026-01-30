<?php

namespace backend\queue;

use backend\controllers\BotController;
use backend\models\PaynetAPI2;

use common\models\Botuser;
use common\models\Payment;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class PaynetQueue extends BaseObject implements JobInterface
{

    public $payment_order;
    public $user;
    public $telegram;
    public $account_number;
    public $amount;
    public $payment_type;
    public $paynet_id;
    public function __construct(array $data)
    {

        $this->user = Botuser::findOne($data['user_id']);
        $this->payment_order = Payment::findOne($data['payment_order_id']);
        $this->account_number = $data['account_number'];
        $this->amount = $data['amount'];
        $this->payment_type = isset($data['payment_type']) ? $data['payment_type'] : null;
        $this->paynet_id = isset($data['paynet_id']) ? $data['paynet_id'] : null;
        $this->telegram = Yii::$app->telegram;
    }

    public function execute($queue)
    {
        try {
            set_time_limit(600);
            $paynet = new PaynetAPI2();

            $isPhone = $this->payment_type == Payment::TO_PHONE;

            $payResult = $isPhone
                ? $paynet->payPhone($this->account_number, $this->amount, $this->paynet_id)
                : $paynet->payCard($this->account_number, $this->amount, $this->paynet_id);

            if ($payResult['status']) {

                $this->payment_order->status = Payment::STATUS_SUCCESS;
                $this->payment_order->save();

                $textToUser = $this->successText(
                    $this->payment_order->account
                );

                $textToChannel = $this->successText(
                    $this->accountMasked($this->payment_order->account)
                );

                $this->sendMessage($textToUser, $this->user->chat_id);
                $this->sendMessage($textToChannel, BotController::PAYMENT_CHANNEL);

            } else {
                $this->cancelPayment(
                    $payResult,
                    $isPhone ? 'Paynet mobile' : 'Paynet Card'
                );
            }

        } catch (\Throwable $e) {
            \Yii::error($e->getMessage());
            throw $e;
        }

        sleep(rand(1, 2));
    }

    private function successText(string $account): string
    {
        return "<b>💸 To'lov o'tkazildi!</b>\n\n"
            . "🆔 #{$this->payment_order->id}\n"
            . "💳 {$account}\n"
            . "💸 {$this->payment_order->amount}\n"
            . "✅ To'langan";
    }


    public function sendMessage($text, $chat_id = null)
    {
        if (is_null($chat_id)) $chat_id = $this->user->chat_id;

        $content = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html'
        ];

        $this->telegram->sendMessage($content);
    }

    public function cancelPayment($result, $message)
    {
        $user = Botuser::findOne($this->user->id);
        $user->balance += $this->amount;
        $user->save();
        $this->payment_order->status = Payment::STATUS_CANCEL;
        $this->payment_order->save();

        $error_text = "#{$message} #UID{$this->user->id} #POID{$this->payment_order->id} #error\n\n";
        $error_text .= json_encode($result, JSON_UNESCAPED_UNICODE);
//        $this->sendMessage($error_text, RobotController::PAYMENT_CHANNEL_ID);
        $text = "<b>To'lov bekor qilindi!</b> \n🆔 #{$this->payment_order->id}\n💳 {$this->payment_order->account}\n🔴 Bekor qilingan";
        $this->sendMessage($text, $this->user->chat_id);
    }

    function accountMasked($account)
    {
        return substr($account, 0, 4) . str_repeat('*', strlen($account) - 6) . substr($account, -2);
    }

}