<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "driver".
 *
 * @property int $id
 * @property string|null $passportBirthdate
 * @property string|null $passportNumber
 * @property string|null $passportSeria
 * @property int|null $relativeId
 * @property int|null $police_id
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Police $police
 * @property Relative $relative
 */
class Driver extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'driver';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passportBirthdate', 'passportNumber', 'passportSeria', 'relativeId', 'police_id'], 'default', 'value' => null],
            [['passportBirthdate', 'created_at', 'updated_at'], 'safe'],
            [['relativeId', 'police_id'], 'integer'],
            [['passportNumber', 'passportSeria'], 'string', 'max' => 255],
            [['police_id'], 'exist', 'skipOnError' => true, 'targetClass' => Police::class, 'targetAttribute' => ['police_id' => 'id']],
            [['relativeId'], 'exist', 'skipOnError' => true, 'targetClass' => Relative::class, 'targetAttribute' => ['relativeId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'passportBirthdate' => Yii::t('app', 'Passport Birthdate'),
            'passportNumber' => Yii::t('app', 'Passport Number'),
            'passportSeria' => Yii::t('app', 'Passport Seria'),
            'relativeId' => Yii::t('app', 'Relative ID'),
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

    /**
     * Gets query for [[Relative]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRelative()
    {
        return $this->hasOne(Relative::class, ['id' => 'relativeId']);
    }

}
