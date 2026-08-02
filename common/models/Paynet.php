<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "paynet".
 *
 * @property int $id
 * @property string|null $name
 * @property int $paynet_id
 * @property string $api_token
 * @property bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Paynet extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paynet';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['paynet_id', 'api_token'], 'required'],
            [['paynet_id'], 'integer'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => false],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'api_token'], 'string', 'max' => 255],
            [['paynet_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Nomi'),
            'paynet_id' => Yii::t('app', 'Paynet ID'),
            'api_token' => Yii::t('app', 'Api Token'),
            'is_active' => Yii::t('app', 'Faol'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Faol Paynet yozuvlari orasidan tasodifiy birini qaytaradi.
     * @return self|null
     */
    public static function getRandomActive(): ?self
    {
        $active = self::find()->where(['is_active' => true])->all();

        return $active ? $active[array_rand($active)] : null;
    }
}
