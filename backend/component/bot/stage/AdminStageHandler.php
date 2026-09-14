<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\models\Pages;
use backend\queue\BroadcastSendJob;
use common\models\Botuser;
use common\models\Broadcast;
use Yii;

/**
 * Pages::ADMIN_PAGE, BROADCAST_PAGE — extracted verbatim from
 * BotController::showAdminPage()/showBroadcastWaitPage()/handleBroadcastPage().
 * Pages::ADMIN_PAGE never appeared in the original inner switch($this->page)
 * (only reachable via the admin-only global commands), so handle() only
 * ever does something for BROADCAST_PAGE — same as before.
 */
class AdminStageHandler implements BotStageInterface
{
    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::ADMIN_PAGE;
        if ($page === Pages::BROADCAST_PAGE) {
            $this->showBroadcastWait($ctx);
            return;
        }

        $this->showAdmin($ctx);
    }

    public function handle(BotContext $ctx): void
    {
        // The original inner switch guarded this case with `if ($this->isAdmin())`
        // — that check lived in actionStart(), not inside handleBroadcastPage()
        // itself, so it's preserved here explicitly.
        if ($ctx->page === Pages::BROADCAST_PAGE && $ctx->isAdmin()) {
            $this->handleBroadcast($ctx);
        }
    }

    public function showAdmin(BotContext $ctx): void
    {
        $ctx->page = Pages::ADMIN_PAGE;
        $option = [
            [$ctx->telegram->buildKeyboardButton("📢 Xabar yuborish")],
            [$ctx->telegram->buildKeyboardButton($ctx->getMText("Main menu"))],
        ];
        $ctx->sendMessageWithKeyborad("⚙️ <b>Admin panel</b>", $option);
    }

    public function showBroadcastWait(BotContext $ctx): void
    {
        $ctx->page = Pages::BROADCAST_PAGE;
        $option = [[$ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel"))]];
        $ctx->sendMessageWithKeyborad("📨 Xabar yuboring yoki forward qiling:", $option);
    }

    public function handleBroadcast(BotContext $ctx): void
    {
        $msgData = $ctx->data['message'] ?? null;
        if (!$msgData) {
            $this->showBroadcastWait($ctx);
            return;
        }

        $typeMap = ['text', 'photo', 'video', 'document', 'audio', 'voice', 'sticker', 'animation', 'video_note'];
        $msgType = 'text';
        foreach ($typeMap as $t) {
            if (isset($msgData[$t])) { $msgType = $t; break; }
        }

        $activeUsers = Botuser::find()->where(['status' => 1])->all();
        $total       = count($activeUsers);

        $broadcast               = new Broadcast();
        $broadcast->message_type = $msgType;
        $broadcast->message_data = json_encode($msgData, JSON_UNESCAPED_UNICODE);
        $broadcast->from_chat_id = (int)$ctx->chat_id;
        $broadcast->total_users  = $total;
        $broadcast->status       = Broadcast::STATUS_SENDING;
        $broadcast->save(false);

        foreach ($activeUsers as $user) {
            Yii::$app->broadcastQueue->push(new BroadcastSendJob([
                'broadcast_id' => $broadcast->id,
                'user_id'      => (int)$user->id,
                'chat_id'      => (int)$user->chat_id,
            ]));
        }

        $ctx->sendMessage("✅ Xabar <b>{$total}</b> ta foydalanuvchiga yuborilmoqda...\n🆔 Broadcast ID: <b>{$broadcast->id}</b>");
        $this->showAdmin($ctx);
    }
}
