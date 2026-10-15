<?php

namespace common\models;

use Yii;

/**
 * Attribution hodisalari tarixi — start/campaign_visit/survey_answered/
 * purchase. Yozish faqat backend\component\MarketingAttributionService
 * orqali amalga oshiriladi.
 *
 * @property int $id
 * @property int $user_id
 * @property string $event
 * @property string|null $source
 * @property string|null $campaign
 * @property string|null $method
 * @property int|null $police_id
 * @property string|null $metadata
 * @property string $created_at
 *
 * @property Botuser $user
 * @property Police|null $police
 */
class MarketingTouch extends \yii\db\ActiveRecord
{
    const EVENT_START = 'start';
    const EVENT_CAMPAIGN_VISIT = 'campaign_visit';
    const EVENT_SURVEY_ANSWERED = 'survey_answered';
    const EVENT_PURCHASE = 'purchase';

    public static function tableName(): string
    {
        return 'marketing_touch';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'event'], 'required'],
            [['user_id', 'police_id'], 'integer'],
            [['event', 'method'], 'string', 'max' => 20],
            [['source'], 'string', 'max' => 30],
            [['campaign'], 'string', 'max' => 20],
            [['metadata'], 'safe'],
            [['created_at'], 'safe'],
            [['user_id'], 'exist', 'targetClass' => Botuser::class, 'targetAttribute' => ['user_id' => 'id']],
            [['police_id'], 'exist', 'skipOnError' => true, 'targetClass' => Police::class, 'targetAttribute' => ['police_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'user_id'    => 'Foydalanuvchi',
            'event'      => 'Hodisa',
            'source'     => 'Manba',
            'campaign'   => 'Kampaniya',
            'method'     => 'Usul',
            'police_id'  => 'Polis',
            'metadata'   => "Qo'shimcha ma'lumot",
            'created_at' => 'Sana',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }

    public function getPolice()
    {
        return $this->hasOne(Police::class, ['id' => 'police_id']);
    }
}
