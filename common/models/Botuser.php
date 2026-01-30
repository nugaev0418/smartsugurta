<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "botuser".
 *
 * @property int $id
 * @property int $chat_id
 * @property int|null $balance
 * @property string|null $fname
 * @property string|null $lname
 * @property string|null $username
 * @property string|null $phone
 * @property int|null $status
 * @property string|null $data
 * @property int|null $is_admin
 * @property int|null $is_banned
 * @property string|null $step
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Police[] $polices
 */
class Botuser extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'botuser';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fname', 'lname', 'username', 'phone', 'data', 'step'], 'default', 'value' => null],
            [['is_banned'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => 1],
            [['chat_id'], 'required'],
            [['chat_id', 'balance', 'status', 'is_admin', 'is_banned'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['fname', 'lname', 'username', 'phone', 'step'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'Chat ID',
            'balance' => 'Balance',
            'fname' => 'Fname',
            'lname' => 'Lname',
            'username' => 'Username',
            'phone' => 'Phone',
            'status' => 'Status',
            'data' => 'Data',
            'is_admin' => 'Is Admin',
            'is_banned' => 'Is Banned',
            'step' => 'Step',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Polices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPolices()
    {
        return $this->hasMany(Police::class, ['user_id' => 'id']);
    }

}
