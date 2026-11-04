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
        bool $viaWebApp = false,
        ?array $bonus = null
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

        if ($bonus !== null && $bonus['percent'] > 0 && $bonus['amount'] > 0) {
            $text = rtrim($text) . "\n" . $this->bonusLine($lang, $bonus['percent'], $bonus['amount']);
        }

        $channelText = $viaWebApp ? "🌐 Web App orqali\n\n" . $text : $text;

        Yii::$app->telegram->sendMessage([
            'chat_id' => EuroAsia::ORDER_CHANNEL_ID,
            'parse_mode' => 'html',
            'text' => $channelText,
            'disable_web_page_preview' => true,
        ]);

        return $text;
    }

    private function bonusLine(string $lang, int $percent, int $amount): string
    {
        $row = Text::findOne(['keyword' => 'confirm_bonus_line']);
        $template = $row && !empty($row->$lang)
            ? $row->$lang
            : ($lang === 'ru' ? "🎁 <b>Бонус (%s%%):</b> %s сум" : "🎁 <b>Bonus (%s%%):</b> %s so'm");

        return sprintf($template, $percent, number_format($amount, 0, '.', ' '));
    }
}
