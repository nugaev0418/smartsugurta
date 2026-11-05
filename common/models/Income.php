<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\db\IntegrityException;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property string $reason
 * @property int $type
 * @property int|null $police_id
 * @property string|null $created_at
 *
 * @property Botuser $user
 */
class Income extends \yii\db\ActiveRecord
{
    public const TYPE_REFERRAL = 1;
    public const TYPE_INSURANCE = 2;

    public static function tableName(): string
    {
        return 'income';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'amount', 'reason'], 'required'],
            [['user_id', 'amount', 'type', 'police_id'], 'integer'],
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
            'type'       => 'Turi',
            'created_at' => 'Sana',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }

    /**
     * Polis uchun bonusni balansga qo'shadi va daromad yozuvini saqlaydi.
     * Shu polis uchun shu turdagi bonus allaqachon yozilgan bo'lsa (unique indeks),
     * balans o'zgarmaydi.
     *
     * @return bool bonus shu chaqiruvda berilgan bo'lsa true
     */
    public static function credit(int $userId, int $amount, string $reason, int $type, int $policeId): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $model = new self();
        $model->user_id   = $userId;
        $model->amount    = $amount;
        $model->reason    = $reason;
        $model->type      = $type;
        $model->police_id = $policeId;

        $transaction = self::getDb()->beginTransaction();
        try {
            $model->save(false);
            Botuser::updateAll(
                ['balance' => new Expression('COALESCE([[balance]], 0) + :amount', [':amount' => $amount])],
                ['id' => $userId]
            );
            $transaction->commit();
            return true;
        } catch (IntegrityException $e) {
            $transaction->rollBack();
            return false;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function referralTotal(int $userId): int
    {
        return (int)(self::find()
            ->where(['user_id' => $userId, 'type' => self::TYPE_REFERRAL])
            ->sum('amount') ?? 0);
    }
}
