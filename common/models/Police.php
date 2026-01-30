<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "police".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $policeId
 * @property string|null $startAt
 * @property string|null $endAt
 * @property string|null $pdfUrl
 * @property int|null $status
 * @property int|null $payment_status
 * @property string|null $paymentId
 * @property string|null $paymentLink
 * @property string|null $gateway
 * @property int|null $amount
 * @property int|null $driverRestriction
 * @property int|null $season_id
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Driver[] $drivers
 * @property Owner[] $owners
 * @property SeasonalInsurance $season
 * @property Botuser $user
 * @property Vehicle[] $vehicles
 */
class Police extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'police';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'policeId', 'startAt', 'endAt', 'pdfUrl', 'paymentId', 'paymentLink', 'gateway', 'amount', 'driverRestriction', 'season_id'], 'default', 'value' => null],
            [['status', 'payment_status'], 'default', 'value' => 0],
            [['user_id', 'status', 'payment_status', 'amount', 'driverRestriction', 'season_id'], 'integer'],
            [['startAt', 'endAt', 'created_at', 'updated_at'], 'safe'],
            [['policeId', 'pdfUrl', 'paymentId', 'gateway'], 'string', 'max' => 255],
            [['season_id'], 'exist', 'skipOnError' => true, 'targetClass' => SeasonalInsurance::class, 'targetAttribute' => ['season_id' => 'id']],
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
            'policeId' => Yii::t('app', 'Police ID'),
            'startAt' => Yii::t('app', 'Start At'),
            'endAt' => Yii::t('app', 'End At'),
            'pdfUrl' => Yii::t('app', 'Pdf Url'),
            'status' => Yii::t('app', 'Status'),
            'payment_status' => Yii::t('app', 'Payment status'),
            'paymentId' => Yii::t('app', 'Payment ID'),
            'paymentLink' => Yii::t('app', 'Payment Link'),
            'gateway' => Yii::t('app', 'Gateway'),
            'amount' => Yii::t('app', 'Amount'),
            'driverRestriction' => Yii::t('app', 'Driver Restriction'),
            'season_id' => Yii::t('app', 'Season ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Drivers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDrivers()
    {
        return $this->hasMany(Driver::class, ['police_id' => 'id']);
    }

    /**
     * Gets query for [[Owners]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwners()
    {
        return $this->hasMany(Owner::class, ['police_id' => 'id']);
    }

    /**
     * Gets query for [[Season]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSeason()
    {
        return $this->hasOne(SeasonalInsurance::class, ['id' => 'season_id']);
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

    /**
     * Gets query for [[Vehicles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVehicles()
    {
        return $this->hasMany(Vehicle::class, ['police_id' => 'id']);
    }

}
