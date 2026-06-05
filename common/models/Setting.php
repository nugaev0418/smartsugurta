<?php

namespace common\models;

/**
 * @property int $id
 * @property int $bot_status
 * @property int $police_status
 * @property int $payment_status
 * @property int $user_percent
 * @property int $tashkent_user_percent
 * @property int $referral_percent
 */
class Setting extends \yii\db\ActiveRecord
{
    private static ?self $_instance = null;

    public static function tableName(): string
    {
        return 'setting';
    }

    public function rules(): array
    {
        return [
            [['bot_status', 'police_status', 'payment_status'], 'in', 'range' => [0, 1]],
            [['user_percent', 'tashkent_user_percent', 'referral_percent'], 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'bot_status'            => 'Bot holati',
            'police_status'         => 'Polis holati',
            'payment_status'        => "To'lov holati",
            'user_percent'          => 'Foydalanuvchi foizi (%)',
            'tashkent_user_percent' => 'Toshkent foydalanuvchi foizi (%)',
            'referral_percent'      => 'Referal foizi (%)',
        ];
    }

    private static function getInstance(): self
    {
        if (self::$_instance === null) {
            self::$_instance = static::find()->one() ?? new static();
        }
        return self::$_instance;
    }

    public static function getBotStatus(): int            { return (int)self::getInstance()->bot_status; }
    public static function getPoliceStatus(): int         { return (int)self::getInstance()->police_status; }
    public static function getPaymentStatus(): int        { return (int)self::getInstance()->payment_status; }
    public static function getUserPercent(): int          { return (int)self::getInstance()->user_percent; }
    public static function getTashkentUserPercent(): int  { return (int)self::getInstance()->tashkent_user_percent; }
    public static function getReferralPercent(): int      { return (int)self::getInstance()->referral_percent; }
}
