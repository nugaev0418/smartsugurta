<?php

namespace backend\component\insurance;

use backend\models\EuroAsia;
use common\models\Text;
use Yii;

/**
 * "confirm texts" andozasini (text jadvali, uz/ru) to'ldirib
 * EuroAsia::ORDER_CHANNEL_ID kanaliga yuboradi — bot
 * (ConfirmStageHandler::show()) va Web App
 * (WebAppController::actionSubmit()) ikkalasi ham shu servisdan
 * foydalanadi, shunda ikkalasida ham bir xil formatdagi xabar boradi.
 * Foydalanuvchiga ko'rsatiladigan (prefikssiz) matnni qaytaradi —
 * chaqiruvchi buni o'z ekranida qayta ishlatishi mumkin.
 */
class OrderChannelNotifier
{
    public function notify(
        string $lang,
        string $autoNumber,
        string $texPass,
        string $arizachi,
        string $phone,
        string $startDateLabel,
        int $periodDays,
        string $endDateLabel,
        string $driversText,
        string $premiumLabel,
        bool $viaWebApp = false
    ): string {
        $row = Text::findOne(['keyword' => 'confirm texts']);
        $template = $row && isset($row->$lang) ? $row->$lang : '';

        $text = sprintf(
            $template,
            $autoNumber,
            $texPass,
            $arizachi,
            $phone,
            $startDateLabel,
            $periodDays,
            $endDateLabel,
            $driversText,
            $premiumLabel
        );

        $channelText = $viaWebApp ? "🌐 Web App orqali\n\n" . $text : $text;

        Yii::$app->telegram->sendMessage([
            'chat_id' => EuroAsia::ORDER_CHANNEL_ID,
            'parse_mode' => 'html',
            'text' => $channelText,
            'disable_web_page_preview' => true,
        ]);

        return $text;
    }
}
