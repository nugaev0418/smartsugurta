<?php

namespace backend\component;

use backend\controllers\BotController;
use Yii;

/**
 * Log xabarlari (foydalanuvchi harakatlari, job holati, API so'rovlari) uchun Telegram log kanali.
 *
 * Kanal id `params['logChannelId']` da (params-local.php), masalan '-1001234567890'. Bot kanalda
 * administrator bo'lishi kerak. Id berilmagan bo'lsa, xabarlar avvalgidek adminga yuboriladi.
 *
 * Log yuborish xatosi (bot kanalga qo'shilmagan va h.k.) hech qachon asosiy oqimni (foydalanuvchi
 * so'rovi, navbat job'i) to'xtatmaydi: u faqat Yii logiga yoziladi.
 *
 * Adminga FAQAT polisa yaratilmaganida ("Polis yaratilmadi") xabar boradi — u JobRetryService orqali
 * yuboriladi, bu klass orqali emas.
 */
class LogChannel
{
    private const MAX_LENGTH = 4000;

    /** Log yuboriladigan chat id (kanal; sozlanmagan bo'lsa admin). */
    public static function chatId(): string
    {
        $channel = Yii::$app->params['logChannelId'] ?? null;

        return ($channel !== null && $channel !== '') ? (string)$channel : (string)BotController::ADMIN_ID;
    }

    public static function send(string $text, ?string $parseMode = null): void
    {
        if (mb_strlen($text) > self::MAX_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_LENGTH) . "\n… (qisqartirildi)";
        }

        $content = ['chat_id' => self::chatId(), 'text' => $text];
        if ($parseMode !== null) {
            $content['parse_mode'] = $parseMode;
        }

        try {
            Yii::$app->telegram->sendMessage($content);
        } catch (\Throwable $e) {
            Yii::error('Log kanaliga xabar yuborilmadi: ' . $e->getMessage(), 'logchannel');
        }
    }
}
