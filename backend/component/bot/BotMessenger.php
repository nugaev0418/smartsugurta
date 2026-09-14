<?php

namespace backend\component\bot;

use Yii;
use yii\base\ErrorException;

/**
 * Every outbound Telegram call BotController makes, extracted verbatim
 * (same content shape, same disable_web_page_preview per call site) so the
 * bot's outward behavior is unchanged. Takes chat_id per call instead of
 * storing it, so it can be constructed once per request and reused for
 * both the current user and admin/channel notifications.
 */
class BotMessenger
{
    private $telegram;
    private string $adminId;

    public function __construct($telegram, string $adminId)
    {
        $this->telegram = $telegram;
        $this->adminId = $adminId;
    }

    public function send(string $chatId, string $text): void
    {
        try {
            $content = ['chat_id' => $chatId, 'parse_mode' => 'html', 'text' => $text];
            $this->telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendAdmin(string $text): void
    {
        $this->send($this->adminId, $text);
    }

    public function sendWithId(string $chatId, string $text): void
    {
        try {
            $content = [
                'chat_id' => $chatId,
                'parse_mode' => 'html',
                'text' => $text,
                'disable_web_page_preview' => true,
            ];
            $this->telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendWithKeyboard(string $chatId, string $text, array $options): void
    {
        try {
            $keyboard = $this->telegram->buildKeyBoard($options, false, true);

            $content = [
                'chat_id' => $chatId,
                'reply_markup' => $keyboard,
                'text' => $text,
                'parse_mode' => 'html',
            ];

            $this->telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendWithInlineKeyboard(string $text, array $options, string $chatId, bool $webView = false): void
    {
        $keyboard = $this->telegram->buildInlineKeyBoard($options);

        $content = [
            'chat_id' => $chatId,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html',
            'text' => $text,
            'disable_web_page_preview' => !$webView,
        ];

        $this->telegram->sendMessage($content);
    }

    public function sendInlineKeyboardAdmin(string $text, array $options): void
    {
        $this->sendWithInlineKeyboard($text, $options, $this->adminId);
    }

    /**
     * Mirrors WebAppController::logToAdmin()'s audit-trail format so both
     * surfaces stay equally traceable. $response may be a DTO object or a
     * plain array — both json_encode() cleanly.
     */
    public function logApiCall(string $chatId, string $label, $request, $response): void
    {
        try {
            $text = "🔌 <b>API so'rovi (Bot)</b>\n"
                . 'Metod: <code>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</code>\n"
                . "Chat ID: <code>{$chatId}</code>\n\n"
                . "So'rov:\n<pre>" . htmlspecialchars(
                    json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    ENT_QUOTES,
                    'UTF-8'
                ) . "</pre>\n\n"
                . "Javob:\n<pre>" . htmlspecialchars(
                    json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    ENT_QUOTES,
                    'UTF-8'
                ) . '</pre>';

            if (mb_strlen($text) > 3900) {
                $text = mb_substr($text, 0, 3900) . "\n… (qisqartirildi)";
            }

            $this->telegram->sendMessage([
                'chat_id' => $this->adminId,
                'parse_mode' => 'html',
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Yii::error("Bot API logini yuborishda xato ({$label}): " . $e->getMessage(), 'bot');
        }
    }
}
