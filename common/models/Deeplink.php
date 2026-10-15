<?php

namespace common\models;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $channel
 * @property string|null $source
 * @property string $cost
 * @property string $currency
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property int $status
 * @property string|null $created_at
 */
class Deeplink extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_ENDED = 0;

    public static function tableName(): string
    {
        return 'deeplink';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 20],
            [['code'], 'unique'],
            [['channel', 'source'], 'string', 'max' => 30],
            [['cost'], 'number', 'min' => 0],
            [['cost'], 'default', 'value' => 0],
            [['currency'], 'string', 'max' => 3],
            [['currency'], 'default', 'value' => 'UZS'],
            [['starts_at', 'ends_at'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_ENDED]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Nom',
            'code'       => 'Kod',
            'channel'    => 'Kanal',
            'source'     => 'Manba',
            'cost'       => 'Xarajat',
            'currency'   => 'Valyuta',
            'starts_at'  => 'Boshlanish sanasi',
            'ends_at'    => 'Tugash sanasi',
            'status'     => 'Holati',
            'created_at' => 'Yaratilgan',
        ];
    }

    public static function getChannelList(): array
    {
        return [
            'instagram' => 'Instagram',
            'telegram'  => 'Telegram',
            'other'     => 'Boshqa',
        ];
    }

    public static function getSourceList(): array
    {
        return [
            'blogger'   => 'Bloger',
            'paid_ads'  => "Pullik reklama",
            'organic'   => 'Organik',
            'other'     => 'Boshqa',
        ];
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_ACTIVE => 'Faol',
            self::STATUS_ENDED  => 'Yakunlangan',
        ];
    }

    public function isActive(): bool
    {
        if ((int)$this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->ends_at && $this->ends_at < date('Y-m-d')) {
            return false;
        }
        return true;
    }

    public function beforeSave($insert): bool
    {
        if ($insert && empty($this->code)) {
            $this->code = self::generateCode();
        }
        return parent::beforeSave($insert);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'dl' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::find()->where(['code' => $code])->exists());
        return $code;
    }

    public function getClickCount(): int
    {
        return (int)Botuser::find()->where(['deeplink_code' => $this->code])->count();
    }

    public function getLink(): string
    {
        return "https://t.me/" . \backend\controllers\BotController::BOT_USERNAME . "?start=" . $this->code;
    }
}
