<?php

namespace common\models;

/**
 * @property int $id
 * @property int $bot_status
 * @property int $police_status
 * @property int $payment_status
 * @property int $my_vehicles_status
 * @property int $admin_online
 * @property int $user_percent
 * @property int $tashkent_user_percent
 * @property int $referral_percent
 * @property int $osago_provider
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
            [['bot_status', 'police_status', 'payment_status', 'my_vehicles_status', 'admin_online'], 'in', 'range' => [0, 1]],
            [['user_percent', 'tashkent_user_percent', 'referral_percent'], 'integer', 'min' => 0, 'max' => 100],
            ['osago_provider', 'in', 'range' => [Police::PROVIDER_GROSS, Police::PROVIDER_UZBEKINVEST]],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'bot_status'            => 'Bot holati',
            'police_status'         => 'Polis holati',
            'payment_status'        => "To'lov holati",
            'my_vehicles_status'    => "Mening avtolarim holati",
            'user_percent'          => 'Foydalanuvchi foizi (%)',
            'tashkent_user_percent' => 'Toshkent foydalanuvchi foizi (%)',
            'referral_percent'      => 'Referal foizi (%)',
            'osago_provider'        => 'OSAGO provayderi',
            'admin_online'          => 'Admin online',
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
    public static function getMyVehiclesStatus(): int     { return (int)self::getInstance()->my_vehicles_status; }
    public static function getUserPercent(): int          { return (int)self::getInstance()->user_percent; }
    public static function getTashkentUserPercent(): int  { return (int)self::getInstance()->tashkent_user_percent; }
    public static function getReferralPercent(): int      { return (int)self::getInstance()->referral_percent; }

    /**
     * Admin online bo'lsa, muvaffaqiyatsiz polisa job'i unga qayta urinish uchun taklif qilinadi.
     * Navbat worker'i uzoq yashaydigan jarayon bo'lgani uchun getInstance() keshini
     * ishlatmaymiz: har safar bazadan o'qiladi, aks holda panelda o'zgartirish ko'rinmay qoladi.
     */
    public static function getAdminOnline(): int
    {
        return (int)static::find()->select('admin_online')->scalar();
    }

    /** Polisa yaratiladigan provayder (Police::PROVIDER_GROSS yoki PROVIDER_UZBEKINVEST). Standart: Gross. */
    public static function getOsagoProvider(): int
    {
        $v = (int)self::getInstance()->osago_provider;
        return $v === Police::PROVIDER_UZBEKINVEST ? $v : Police::PROVIDER_GROSS;
    }
}
