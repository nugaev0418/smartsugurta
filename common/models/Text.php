<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "text".
 *
 * @property int $id
 * @property string|null $keyword
 * @property string|null $uz
 * @property string|null $ru
 */
class Text extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['keyword', 'uz', 'ru'], 'default', 'value' => null],
            [['keyword', 'uz', 'ru'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'keyword' => Yii::t('app', 'Keyword'),
            'uz' => Yii::t('app', 'Uz'),
            'ru' => Yii::t('app', 'Ru'),
        ];
    }

}
