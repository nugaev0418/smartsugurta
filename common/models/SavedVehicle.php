<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "saved_vehicle".
 *
 * @property int $id
 * @property int $botuser_id
 * @property string|null $gov_number
 * @property string|null $tech_passport_seria
 * @property string|null $tech_passport_number
 * @property string|null $owner_type
 * @property string|null $model
 * @property string|null $vehicle_type_name
 * @property string $ersp_check_status
 * @property string|null $ersp_policies_json
 * @property string|null $ersp_checked_at
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Botuser $botuser
 */
class SavedVehicle extends \yii\db\ActiveRecord
{
    const ERSP_STATUS_IDLE     = 'idle';
    const ERSP_STATUS_CHECKING = 'checking';
    const ERSP_STATUS_DONE     = 'done';
    const ERSP_STATUS_FAILED   = 'failed';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'saved_vehicle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['botuser_id'], 'required'],
            [['botuser_id'], 'integer'],
            [['ersp_policies_json'], 'string'],
            [['ersp_checked_at', 'created_at', 'updated_at'], 'safe'],
            [['ersp_check_status'], 'default', 'value' => self::ERSP_STATUS_IDLE],
            [['gov_number', 'tech_passport_seria', 'tech_passport_number', 'owner_type', 'model', 'vehicle_type_name', 'ersp_check_status'], 'string', 'max' => 255],
            [['botuser_id'], 'exist', 'skipOnError' => true, 'targetClass' => Botuser::class, 'targetAttribute' => ['botuser_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'botuser_id' => Yii::t('app', 'Botuser ID'),
            'gov_number' => Yii::t('app', 'Gov Number'),
            'tech_passport_seria' => Yii::t('app', 'Tech Passport Seria'),
            'tech_passport_number' => Yii::t('app', 'Tech Passport Number'),
            'owner_type' => Yii::t('app', 'Owner Type'),
            'model' => Yii::t('app', 'Model'),
            'vehicle_type_name' => Yii::t('app', 'Vehicle Type Name'),
            'ersp_check_status' => Yii::t('app', 'Ersp Check Status'),
            'ersp_policies_json' => Yii::t('app', 'Ersp Policies Json'),
            'ersp_checked_at' => Yii::t('app', 'Ersp Checked At'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Botuser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBotuser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'botuser_id']);
    }

    /**
     * Oxirgi ERSP tekshiruvidan qolgan xom polisalar ro'yxati (ErspVehicleClient::extractPolicies()
     * formatida) — hali tekshirilmagan yoki tekshiruv muvaffaqiyatsiz bo'lsa bo'sh massiv.
     *
     * @return array
     */
    public function getCachedPolicies(): array
    {
        if (!$this->ersp_policies_json) {
            return [];
        }

        $decoded = json_decode($this->ersp_policies_json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
