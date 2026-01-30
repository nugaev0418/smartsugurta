<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "vehicle".
 *
 * @property int $id
 * @property string|null $licenseNumber
 * @property string|null $techPassportNumber
 * @property string|null $techPassportSeria
 * @property int|null $police_id
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Police $police
 */
class Vehicle extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vehicle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['licenseNumber', 'techPassportNumber', 'techPassportSeria', 'police_id'], 'default', 'value' => null],
            [['police_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['licenseNumber', 'techPassportNumber', 'techPassportSeria'], 'string', 'max' => 255],
            [['police_id'], 'exist', 'skipOnError' => true, 'targetClass' => Police::class, 'targetAttribute' => ['police_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'licenseNumber' => Yii::t('app', 'License Number'),
            'techPassportNumber' => Yii::t('app', 'Tech Passport Number'),
            'techPassportSeria' => Yii::t('app', 'Tech Passport Seria'),
            'police_id' => Yii::t('app', 'Police ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Police]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolice()
    {
        return $this->hasOne(Police::class, ['id' => 'police_id']);
    }

}
