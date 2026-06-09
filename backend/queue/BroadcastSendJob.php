<?php

namespace backend\queue;

use common\models\Broadcast;
use common\models\BroadcastLog;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class BroadcastSendJob extends BaseObject implements JobInterface
{
    public int $broadcast_id;
    public int $user_id;
    public int $chat_id;

    public function execute($queue): void
    {
        $broadcast = Broadcast::findOne($this->broadcast_id);
        if (!$broadcast) {
            return;
        }

        $msgData = json_decode($broadcast->message_data, true);
        $msgId   = $msgData['message_id'] ?? null;

        if (!$msgId) {
            return;
        }

        $telegram      = Yii::$app->telegram;
        $telegramMsgId = null;

        try {
            $result = $telegram->copyMessage([
                'chat_id'      => $this->chat_id,
                'from_chat_id' => $broadcast->from_chat_id,
                'message_id'   => $msgId,
            ]);

            if (isset($result['result']['message_id'])) {
                $telegramMsgId = $result['result']['message_id'];
            }
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            Yii::warning("BroadcastSendJob user {$this->user_id}: {$errMsg}", 'broadcast');

            // Bot bloklangan yoki user mavjud emas → statusni 0 qilib qo'yish
            if (str_contains($errMsg, 'bot was blocked')
                || str_contains($errMsg, 'user is deactivated')
                || str_contains($errMsg, 'chat not found')
                || str_contains($errMsg, 'Forbidden')
            ) {
                \common\models\Botuser::updateAll(['status' => 0], ['id' => $this->user_id]);
            }
        }

        $log                      = new BroadcastLog();
        $log->broadcast_id        = $this->broadcast_id;
        $log->user_id             = $this->user_id;
        $log->chat_id             = $this->chat_id;
        $log->telegram_message_id = $telegramMsgId;
        $log->save(false);

        Broadcast::updateAllCounters(['sent_count' => 1], ['id' => $this->broadcast_id]);

        $broadcast->refresh();
        if ($broadcast->sent_count >= $broadcast->total_users) {
            $broadcast->status = Broadcast::STATUS_DONE;
            $broadcast->save(false);
        }
    }
}
