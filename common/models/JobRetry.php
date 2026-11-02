<?php

namespace common\models;

/**
 * Muvaffaqiyatsiz tugagan polisa job'ini admin qayta urinishga yuborishi uchun taklif.
 *
 * @property int $id
 * @property int $chat_id Foydalanuvchi chat_id
 * @property string $application_data OsagoApplicationData (JSON)
 * @property int $failed_provider Police::PROVIDER_* — yiqilgan provayder
 * @property string|null $reason
 * @property int $status STATUS_*
 * @property int|null $retried_provider
 * @property string|null $created_at
 * @property string|null $resolved_at
 */
class JobRetry extends \yii\db\ActiveRecord
{
    public const STATUS_PENDING   = 0;
    public const STATUS_RETRIED   = 1;
    public const STATUS_EXPIRED   = 2;
    public const STATUS_CANCELLED = 3;
    public const STATUS_FAILED    = 4;

    public static function tableName(): string
    {
        return 'job_retry';
    }

    public function rules(): array
    {
        return [
            [['chat_id', 'application_data', 'failed_provider'], 'required'],
            [['chat_id', 'failed_provider', 'status', 'retried_provider'], 'integer'],
            [['application_data'], 'string'],
            [['reason'], 'string', 'max' => 512],
            [['created_at', 'resolved_at'], 'safe'],
        ];
    }
}
