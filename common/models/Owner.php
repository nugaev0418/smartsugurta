<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "owner".
 *
 * @property int $id
 * @property int|null $inn
 * @property string|null $name
 * @property string|null $address
 * @property string|null $type
 * @property string|null $firstName
 * @property string|null $middlename
 * @property string|null $lastname
 * @property int|null $pinfl
 * @property string|null $districtId
 * @property int|null $police_id
 *
 * @property Police $police
 */
class Owner extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'owner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['inn', 'name', 'address', 'type', 'firstName', 'middlename', 'lastname', 'pinfl', 'districtId', 'police_id'], 'default', 'value' => null],
            [['inn', 'pinfl', 'police_id'], 'integer'],
            [['name', 'address', 'type', 'firstName', 'middlename', 'lastname', 'districtId'], 'string', 'max' => 255],
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
            'inn' => Yii::t('app', 'Inn'),
            'name' => Yii::t('app', 'Name'),
            'address' => Yii::t('app', 'Address'),
            'type' => Yii::t('app', 'Type'),
            'firstName' => Yii::t('app', 'First Name'),
            'middlename' => Yii::t('app', 'Middlename'),
            'lastname' => Yii::t('app', 'Lastname'),
            'pinfl' => Yii::t('app', 'Pinfl'),
            'districtId' => Yii::t('app', 'District ID'),
            'police_id' => Yii::t('app', 'Police ID'),
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
