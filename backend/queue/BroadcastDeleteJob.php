<?php

namespace backend\queue;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class BroadcastDeleteJob extends BaseObject implements JobInterface
{
    public int $chat_id;
    public int $telegram_message_id;

    public function execute($queue): void
    {
        try {
            Yii::$app->telegram->deleteMessage([
                'chat_id'    => $this->chat_id,
                'message_id' => $this->telegram_message_id,
            ]);
        } catch (\Throwable $e) {
            Yii::warning("BroadcastDeleteJob chat {$this->chat_id} msg {$this->telegram_message_id}: " . $e->getMessage(), 'broadcast');
        }
    }
}
