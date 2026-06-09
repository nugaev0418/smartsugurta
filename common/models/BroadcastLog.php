<?php

namespace common\models;

/**
 * @property int $id
 * @property int $broadcast_id
 * @property int $user_id
 * @property int $chat_id
 * @property int|null $telegram_message_id
 * @property string|null $created_at
 *
 * @property Broadcast $broadcast
 * @property Botuser $user
 */
class BroadcastLog extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'broadcast_log';
    }

    public function rules(): array
    {
        return [
            [['broadcast_id', 'user_id', 'chat_id'], 'required'],
            [['broadcast_id', 'user_id', 'chat_id', 'telegram_message_id'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    public function getBroadcast()
    {
        return $this->hasOne(Broadcast::class, ['id' => 'broadcast_id']);
    }

    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }
}
