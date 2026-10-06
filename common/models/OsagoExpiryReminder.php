<?php

namespace common\models;

/**
 * @property int $id
 * @property int $saved_vehicle_id
 * @property string $policy_end_date
 * @property int $milestone_days
 * @property string|null $sent_at
 *
 * @property SavedVehicle $savedVehicle
 */
class OsagoExpiryReminder extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'osago_expiry_reminder';
    }

    public function rules(): array
    {
        return [
            [['saved_vehicle_id', 'policy_end_date', 'milestone_days'], 'required'],
            [['saved_vehicle_id', 'milestone_days'], 'integer'],
            [['policy_end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['sent_at'], 'safe'],
        ];
    }

    public function getSavedVehicle()
    {
        return $this->hasOne(SavedVehicle::class, ['id' => 'saved_vehicle_id']);
    }
}
