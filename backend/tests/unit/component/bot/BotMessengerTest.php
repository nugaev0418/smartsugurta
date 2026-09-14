<?php

namespace backend\tests\unit\component\bot;

use backend\component\bot\BotMessenger;

class BotMessengerTest extends \Codeception\Test\Unit
{
    private function stubTelegram(): object
    {
        return new class {
            public array $calls = [];
            public function sendMessage(array $content) { $this->calls[] = $content; }
            public function buildKeyBoard(array $options, $onetime = false, $resize = false, $selective = true) { return ['kb' => $options, 'onetime' => $onetime, 'resize' => $resize]; }
            public function buildInlineKeyBoard(array $options) { return ['ikb' => $options]; }
        };
    }

    public function testSendUsesPlainContentShape()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->send('111', 'hello');

        $this->assertSame(['chat_id' => '111', 'parse_mode' => 'html', 'text' => 'hello'], $tg->calls[0]);
    }

    public function testSendAdminUsesAdminChatId()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->sendAdmin('admin-hello');

        $this->assertSame('3673579', $tg->calls[0]['chat_id']);
    }

    public function testSendWithIdSetsDisableWebPagePreview()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->sendWithId('222', 'with-id');

        $this->assertSame(
            ['chat_id' => '222', 'parse_mode' => 'html', 'text' => 'with-id', 'disable_web_page_preview' => true],
            $tg->calls[0]
        );
    }

    public function testSendWithKeyboardBuildsResizedNonOnetimeKeyboard()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->sendWithKeyboard('333', 'kb-text', [['a']]);

        $this->assertSame([
            'chat_id' => '333',
            'reply_markup' => ['kb' => [['a']], 'onetime' => false, 'resize' => true],
            'text' => 'kb-text',
            'parse_mode' => 'html',
        ], $tg->calls[0]);
    }

    public function testSendWithInlineKeyboardWebViewFlag()
    {
        $tg = $this->stubTelegram();
        $m = new BotMessenger($tg, '3673579');

        $m->sendWithInlineKeyboard('a', [['x']], '444', false);
        $this->assertTrue($tg->calls[0]['disable_web_page_preview']);

        $m->sendWithInlineKeyboard('b', [['y']], '444', true);
        $this->assertFalse($tg->calls[1]['disable_web_page_preview']);
    }

    public function testSendInlineKeyboardAdminTargetsAdminChatId()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->sendInlineKeyboardAdmin('admin-inline', [['d']]);

        $this->assertSame('3673579', $tg->calls[0]['chat_id']);
    }

    public function testLogApiCallTargetsAdminAndIncludesLabelAndChatId()
    {
        $tg = $this->stubTelegram();
        (new BotMessenger($tg, '3673579'))->logApiCall('999', 'Label::method', ['req' => 1], ['resp' => 2]);

        $logged = $tg->calls[0];
        $this->assertSame('3673579', $logged['chat_id']);
        $this->assertStringContainsString('Chat ID: <code>999</code>', $logged['text']);
        $this->assertStringContainsString('Label::method', $logged['text']);
    }

    public function testLogApiCallTruncatesVeryLongPayloads()
    {
        $tg = $this->stubTelegram();
        $bigRequest = ['data' => str_repeat('x', 5000)];
        (new BotMessenger($tg, '3673579'))->logApiCall('999', 'Label', $bigRequest, []);

        $this->assertLessThanOrEqual(4000, mb_strlen($tg->calls[0]['text']));
        $this->assertStringContainsString('qisqartirildi', $tg->calls[0]['text']);
    }
}
