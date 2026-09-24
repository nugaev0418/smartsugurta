<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\ReferralService;
use backend\controllers\BotController;
use backend\models\Pages;
use common\models\Setting;
use Yii;
use yii\base\ErrorException;
use yii\helpers\Url;

/**
 * The main menu (Pages::MAIN) plus the other page-agnostic instant-reply
 * commands reachable from it (Support/Prices/Referral). Extracted verbatim
 * from BotController::showMainPage()/showSupportPage()/showPricesPage()/
 * showReferralPage(). Pages::MAIN never appeared in the original inner
 * switch($this->page), so handle() is a no-op — free text sent while on
 * the main menu produced no response before either.
 */
class MainMenuStageHandler implements BotStageInterface
{
    public function show(BotContext $ctx, array $params = []): void
    {
        $text = $params['text'] ?? false;

        try {
            $ctx->page = Pages::MAIN;

            if (!$text) {
                $text = $ctx->getMText('hello');
            }

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("BEGIN OSAGO BUTTON")),
                    $ctx->telegram->buildKeyboardButton($ctx->getMText('Wallet')),
                ],
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText('Referral system')),
                    $ctx->telegram->buildKeyboardButton($ctx->getMText('Prices')),
                ],
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText('Language selection')),
                    $ctx->telegram->buildKeyboardButton($ctx->getMText('Support')),
                ],
            ];
            // "Mening avtolarim" — admin-only emas, Setting::getMyVehiclesStatus()
            // orqali hamma foydalanuvchi uchun yoqilishi/o'chirilishi mumkin
            // (Admin panel → Bot sozlamalari).
            if (Setting::getMyVehiclesStatus()) {
                $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('My vehicles menu button'))];
            }
            if ($ctx->isAdmin()) {
                $option[] = [$ctx->telegram->buildKeyboardButton("⚙️ Admin panel")];
            }
            $ctx->sendMessageWithKeyborad($text, $option);

            if ($ctx->isAdmin()) {
                // Reply-keyboard "web_app" buttons don't receive signed initData from Telegram,
                // so the Web App is offered via an inline button on a separate message instead.
                $ctx->sendMessageWithInlineKeyboard(
                    "🌐 Web App orqali sug'urta rasmiylashtirish",
                    [[
                        ['text' => '🌐 Web App', 'web_app' => ['url' => Url::base('https') . '/webapp/index.html']],
                    ]]
                );
            }

            if (Setting::getMyVehiclesStatus()) {
                $ctx->sendMessageWithInlineKeyboard(
                    $ctx->getMText('My vehicles menu button'),
                    [[
                        ['text' => $ctx->getMText('My vehicles menu button'), 'web_app' => ['url' => Url::base('https') . '/webapp/index.html?screen=my-vehicles']],
                    ]]
                );
            }
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function handle(BotContext $ctx): void
    {
        // Pages::MAIN had no case in the original inner switch — nothing to do.
    }

    public function showSupport(BotContext $ctx): void
    {
        $text = $ctx->getMText('support page message');

        $ctx->sendMessage($text);
    }

    public function showPrices(BotContext $ctx): void
    {
        $text = $ctx->getMText('prices page message');

        $ctx->sendMessage($text);
    }

    public function showReferral(BotContext $ctx): void
    {
        $data = (new ReferralService(BotController::BOT_USERNAME))->buildPageData($ctx->chat_id);
        $inline = [
            [$ctx->telegram->buildInlineKeyboardButton($data['shareLabel'], $data['shareUrl'])],
        ];
        $ctx->sendMessageWithInlineKeyboard($data['text'], $inline);
    }
}
