<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "season".
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $days
 * @property string|null $seasonId
 *
 * @property Police[] $polices
 */
class SeasonalInsurance extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seasonalInsurance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'days', 'seasonId'], 'default', 'value' => null],
            [['days'], 'integer'],
            [['name', 'seasonId'], 'string', 'max' => 255],
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
            'days' => Yii::t('app', 'Days'),
            'seasonId' => Yii::t('app', 'Season ID'),
        ];
    }

    /**
     * Gets query for [[Polices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolices()
    {
        return $this->hasMany(Police::class, ['season_id' => 'id']);
    }

}
