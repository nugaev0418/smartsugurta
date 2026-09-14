<?php

namespace backend\component\bot;

/**
 * Everything a stage handler needs to process one incoming Telegram update:
 * the raw per-request fields (chat_id/text/data/telegram) plus the shared
 * infrastructure (state/messenger/textService). Mirrors the public property
 * and method names BotController itself used to expose, so extracted
 * show*Page()/handle*Page() bodies only need "$this->" renamed to "$ctx->".
 *
 * Undeclared property access (->page, ->phone, ->lisenceNumber, ->drivers,
 * ...) falls through to __get/__set, which read/write BotUserState — the
 * same Botuser.data JSON blob the FSM has always used.
 */
class BotContext
{
    public $chat_id;
    public $text;
    public $data;
    public $telegram;

    public BotUserState $state;

    private BotMessenger $messenger;
    private BotTextService $textService;
    private string $adminId;

    public function __construct(
        $chat_id,
        $text,
        $data,
        $telegram,
        BotUserState $state,
        BotMessenger $messenger,
        BotTextService $textService,
        string $adminId
    ) {
        $this->chat_id = $chat_id;
        $this->text = $text;
        $this->data = $data;
        $this->telegram = $telegram;
        $this->state = $state;
        $this->messenger = $messenger;
        $this->textService = $textService;
        $this->adminId = $adminId;
    }

    public function __get($name)
    {
        return $this->state->get($name);
    }

    public function __set($name, $value)
    {
        $this->state->set($name, $value);
    }

    public function isAdmin(): bool
    {
        return (string)$this->chat_id === (string)$this->adminId;
    }

    public function getMText($keyword)
    {
        return $this->textService->m($this->lang, $keyword);
    }

    public function getKeywordText($text)
    {
        return $this->textService->keywordOf($this->lang, $text);
    }

    public function sendMessage($text)
    {
        $this->messenger->send($this->chat_id, $text);
    }

    public function sendMessageAdmin($text)
    {
        $this->messenger->sendAdmin($text);
    }

    public function sendMessageWithID($chat_id, $text)
    {
        $this->messenger->sendWithId($chat_id, $text);
    }

    public function sendMessageWithKeyborad($text, $option)
    {
        $this->messenger->sendWithKeyboard($this->chat_id, $text, $option);
    }

    public function sendMessageWithInlineKeyboard($text, $option, $chat_id = null, $web_view = false)
    {
        if (is_null($chat_id)) {
            $chat_id = $this->chat_id;
        }

        $this->messenger->sendWithInlineKeyboard($text, $option, $chat_id, $web_view);
    }

    public function logApiCall(string $label, $request, $response): void
    {
        $this->messenger->logApiCall($this->chat_id, $label, $request, $response);
    }
}
