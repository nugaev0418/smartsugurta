<?php

namespace common\models;

use Yii;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property string $reason
 * @property string|null $created_at
 *
 * @property Botuser $user
 */
class Income extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return 'income';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'amount', 'reason'], 'required'],
            [['user_id', 'amount'], 'integer'],
            [['reason'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
            [['user_id'], 'exist', 'targetClass' => Botuser::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'user_id'    => 'Foydalanuvchi',
            'amount'     => 'Miqdor',
            'reason'     => 'Sabab',
            'created_at' => 'Sana',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }

    public static function add(int $userId, int $amount, string $reason): void
    {
        $model = new self();
        $model->user_id = $userId;
        $model->amount  = $amount;
        $model->reason  = $reason;
        $model->save(false);
    }
}
