<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "relative".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $relativeId
 *
 * @property Driver[] $drivers
 */
class Relative extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'relative';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'relativeId'], 'default', 'value' => null],
            [['name', 'relativeId'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'relativeId' => Yii::t('app', 'Relative ID'),
        ];
    }

    /**
     * Gets query for [[Drivers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDrivers()
    {
        return $this->hasMany(Driver::class, ['relativeId' => 'id']);
    }

}
