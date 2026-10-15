<?php

namespace common\models;

use Yii;

/**
 * Bitta Telegram foydalanuvchining joriy marketing-attribution holati
 * (bitta qator/foydalanuvchi). Yozish faqat
 * backend\component\MarketingAttributionService orqali amalga oshiriladi.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $first_source
 * @property string|null $first_campaign
 * @property string|null $last_source
 * @property string|null $last_campaign
 * @property string|null $source_method
 * @property string $first_seen_at
 * @property string $last_seen_at
 * @property string|null $survey_source
 * @property string|null $survey_at
 *
 * @property Botuser $user
 */
class MarketingAttribution extends \yii\db\ActiveRecord
{
    const METHOD_DEEPLINK = 'deeplink';
    const METHOD_REFERRAL = 'referral';
    const METHOD_SURVEY = 'survey';
    const METHOD_DIRECT = 'direct';

    // SurveyStageHandler'dagi so'rovnoma variantlariga mos kalitlar —
    // admin panelda (MarketingReportService) ko'rsatish uchun yagona manba.
    const SURVEY_LABELS = [
        'instagram_blogger' => 'Instagram blogeridan',
        'instagram_ads' => 'Instagram reklamasi',
        'telegram_channel' => 'Telegram kanalidan',
        'friend_referral' => "Do'stim tavsiya qildi",
        'google_other' => 'Google yoki boshqa manba',
        'other_unknown' => "Boshqa / eslay olmayman",
    ];

    public static function tableName(): string
    {
        return 'marketing_attribution';
    }

    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['user_id'], 'exist', 'targetClass' => Botuser::class, 'targetAttribute' => ['user_id' => 'id']],
            [['first_source', 'last_source', 'survey_source'], 'string', 'max' => 30],
            [['first_campaign', 'last_campaign'], 'string', 'max' => 20],
            [['source_method'], 'string', 'max' => 20],
            [['first_seen_at', 'last_seen_at', 'survey_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'user_id'        => 'Foydalanuvchi',
            'first_source'   => 'Birinchi manba',
            'first_campaign' => 'Birinchi kampaniya',
            'last_source'    => 'Oxirgi manba',
            'last_campaign'  => 'Oxirgi kampaniya',
            'source_method'  => 'Aniqlash usuli',
            'first_seen_at'  => 'Birinchi ko\'rinish',
            'last_seen_at'   => 'Oxirgi ko\'rinish',
            'survey_source'  => "So'rovnoma javobi",
            'survey_at'      => "So'rovnoma vaqti",
        ];
    }

    public function getUser()
    {
        return $this->hasOne(Botuser::class, ['id' => 'user_id']);
    }
}
