<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "payment".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $type
 * @property string|null $account
 * @property int|null $amount
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $payment_id
 * @property string|null $updated_at
 *
 * @property Botuser $user
 */
class Payment extends \yii\db\ActiveRecord
{

    const
        TO_CARD = 1,
        TO_PHONE = 2;
    const STATUS_PROCESS = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_CANCEL = 2;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'account', 'amount', 'payment_id'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 0],
            [['user_id', 'type', 'amount', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['account', 'payment_id'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Botuser::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'account' => Yii::t('app', 'Account'),
            'amount' => Yii::t('app', 'Amount'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'payment_id' => Yii::t('app', 'Payment ID'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }

}
