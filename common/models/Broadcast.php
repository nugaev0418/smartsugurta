<?php

namespace common\models;

/**
 * @property int $id
 * @property string $message_type
 * @property string $message_data
 * @property int $from_chat_id
 * @property int $total_users
 * @property int $sent_count
 * @property int $status
 * @property string|null $created_at
 *
 * @property BroadcastLog[] $logs
 */
class Broadcast extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_SENDING = 1;
    const STATUS_DONE    = 2;

    public static function tableName(): string
    {
        return 'broadcast';
    }

    public function rules(): array
    {
        return [
            [['message_type', 'message_data', 'from_chat_id'], 'required'],
            [['from_chat_id', 'total_users', 'sent_count', 'status'], 'integer'],
            [['message_data'], 'string'],
            [['message_type'], 'string', 'max' => 50],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'           => 'ID',
            'message_type' => 'Xabar turi',
            'from_chat_id' => 'Admin chat ID',
            'total_users'  => 'Jami foydalanuvchi',
            'sent_count'   => 'Yuborildi',
            'status'       => 'Holat',
            'created_at'   => 'Sana',
        ];
    }

    public function getProgressPercent(): int
    {
        if ($this->total_users <= 0) return 0;
        return (int)min(100, round($this->sent_count / $this->total_users * 100));
    }

    public function getStatusLabel(): string
    {
        return match ((int)$this->status) {
            self::STATUS_SENDING => 'Yuborilmoqda',
            self::STATUS_DONE    => 'Tugadi',
            default              => 'Kutmoqda',
        };
    }

    public function getLogs()
    {
        return $this->hasMany(BroadcastLog::class, ['broadcast_id' => 'id']);
    }
}
