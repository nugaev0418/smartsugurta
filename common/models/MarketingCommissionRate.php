<?php

namespace common\models;

use common\models\Police;
use Yii;

/**
 * Provayder (Police::PROVIDER_*) va hudud bo'yicha komissiya foizi —
 * admin paneldan sozlanadi, ROAS/net_result hisoblashda ishlatiladi.
 *
 * @property int $id
 * @property int $provider_id
 * @property string $region
 * @property string $percent
 * @property string $created_at
 * @property string $updated_at
 */
class MarketingCommissionRate extends \yii\db\ActiveRecord
{
    const REGION_TASHKENT = 'tashkent';
    const REGION_OTHER = 'other';

    public static function tableName(): string
    {
        return 'marketing_commission_rate';
    }

    public function rules(): array
    {
        return [
            [['provider_id', 'region', 'percent'], 'required'],
            [['provider_id'], 'integer'],
            [['region'], 'in', 'range' => [self::REGION_TASHKENT, self::REGION_OTHER]],
            [['percent'], 'number', 'min' => 0, 'max' => 100],
            [['created_at', 'updated_at'], 'safe'],
            [
                ['provider_id', 'region'],
                'unique',
                'targetAttribute' => ['provider_id', 'region'],
                'message' => 'Bu provayder va hudud uchun stavka allaqachon mavjud.',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'provider_id' => 'Provayder',
            'region'      => 'Hudud',
            'percent'     => 'Komissiya, %',
            'created_at'  => 'Yaratilgan',
            'updated_at'  => 'Yangilangan',
        ];
    }

    public static function getRegionList(): array
    {
        return [
            self::REGION_TASHKENT => 'Toshkent (shahar/viloyat)',
            self::REGION_OTHER    => 'Qolgan viloyatlar',
        ];
    }

    public static function findPercent(int $providerId, string $region): ?float
    {
        $model = self::find()
            ->where(['provider_id' => $providerId, 'region' => $region])
            ->one();

        return $model ? (float)$model->percent : null;
    }
}
