<?php

namespace backend\component;

use backend\component\insurance\OsagoRequestBuilder;
use common\models\Botuser;
use common\models\Deeplink;
use common\models\MarketingAttribution;
use common\models\MarketingCommissionRate;
use common\models\MarketingTouch;
use common\models\Police;
use Yii;

/**
 * Yagona yozish nuqtasi marketing_attribution/marketing_touch jadvallari
 * uchun. Attribution qatori har doim Yii's upsert() orqali yoziladi — bu
 * MySQL'ning "INSERT ... ON DUPLICATE KEY UPDATE"siga tayanadi, shuning
 * uchun first_source/first_campaign/first_seen_at FAQAT qatorning birinchi
 * marta yaratilishida (haqiqiy DB holati, $isNewUser bayrog'iga qarab EMAS)
 * yoziladi va keyin hech qachon qayta yozilmaydi — parallel takroriy
 * so'rovlarda ham (race-safe, DB darajasidagi unique user_id orqali).
 */
class MarketingAttributionService
{
    // "unknown" — kod topilmadi/muddati tugagan/o'chirilgan (rule 3: ishonchli
    // manba sifatida hisoblanmaydi). "campaign" — kod HAQIQIY va faol, shunchaki
    // admin hali shu kampaniyaga kanal/manba belgilamagan — bu ikkisi boshqa-
    // boshqa holat, bir xil qiymatga qisqartirilmasligi kerak (aks holda haqiqiy
    // kampaniya "yaroqsiz havola" deb noto'g'ri ko'rsatiladi).
    const SOURCE_UNKNOWN = 'unknown';
    const SOURCE_CAMPAIGN_UNSPECIFIED = 'campaign';
    const SOURCE_REFERRAL = 'referral';

    public function recordDeeplinkTouch(string $chatId, ?Deeplink $deeplink, bool $isNewUser): void
    {
        if ($deeplink && $deeplink->isActive()) {
            $this->recordTouch(
                $chatId,
                $deeplink->source ?: $deeplink->channel ?: self::SOURCE_CAMPAIGN_UNSPECIFIED,
                $deeplink->code,
                MarketingAttribution::METHOD_DEEPLINK,
                $isNewUser
            );
            return;
        }

        $this->recordTouch($chatId, self::SOURCE_UNKNOWN, null, MarketingAttribution::METHOD_DEEPLINK, $isNewUser);
    }

    public function recordReferralTouch(string $chatId, bool $isNewUser): void
    {
        $this->recordTouch($chatId, self::SOURCE_REFERRAL, null, MarketingAttribution::METHOD_REFERRAL, $isNewUser);
    }

    /**
     * ref_ kodi formati mos keldi, lekin aslida yangi referral bog'lanish
     * o'rnatilmadi (eski/yaroqsiz kod, o'z-o'ziga referral yoki
     * foydalanuvchi allaqachon boshqa referral bilan bog'langan). Rule 3
     * ruhida — ishonchli manba sifatida yozilmaydi, faqat "noma'lum".
     */
    public function recordUnknownReferralAttempt(string $chatId, bool $isNewUser): void
    {
        $this->recordTouch($chatId, self::SOURCE_UNKNOWN, null, MarketingAttribution::METHOD_REFERRAL, $isNewUser);
    }

    private function recordTouch(string $chatId, string $source, ?string $campaign, string $method, bool $isNewUser): void
    {
        $userId = $this->resolveUserId($chatId);
        if (!$userId) {
            return;
        }

        $this->upsertAttribution($userId, $source, $campaign, $method);

        $this->logTouch(
            $userId,
            $isNewUser ? MarketingTouch::EVENT_START : MarketingTouch::EVENT_CAMPAIGN_VISIT,
            $source,
            $campaign,
            $method
        );
    }

    /**
     * Na deeplink, na referral payload mos kelmagan oddiy /start. Faqat
     * yangi foydalanuvchi uchun boshlang'ich attribution qatorini yaratadi
     * (source_method='direct') — mavjud foydalanuvchi uchun esa last_source
     * O'ZGARTIRILMAYDI (faqat last_seen_at), chunki oddiy /start eski
     * manbani o'chirib yubormasligi kerak.
     */
    public function recordDirectTouch(string $chatId, bool $isNewUser): void
    {
        $userId = $this->resolveUserId($chatId);
        if (!$userId) {
            return;
        }

        if ($isNewUser) {
            $this->upsertAttribution($userId, MarketingAttribution::METHOD_DIRECT, null, MarketingAttribution::METHOD_DIRECT);
            $this->logTouch($userId, MarketingTouch::EVENT_START, MarketingAttribution::METHOD_DIRECT, null, MarketingAttribution::METHOD_DIRECT);
            return;
        }

        Yii::$app->db->createCommand()->update(
            MarketingAttribution::tableName(),
            ['last_seen_at' => new \yii\db\Expression('NOW()')],
            ['user_id' => $userId]
        )->execute();
    }

    /**
     * Faqat hali ishonchli manba aniqlanmagan (source_method direct/null)
     * foydalanuvchilar uchun ishlaydi — deeplink/referral orqali kelgan
     * userning allaqachon ma'lum bo'lgan manbasini savol bilan bosib
     * yozmaydi.
     */
    public function recordSurveyAnswer(string $chatId, string $surveyKey): void
    {
        $userId = $this->resolveUserId($chatId);
        if (!$userId) {
            return;
        }

        $attribution = MarketingAttribution::findOne(['user_id' => $userId]);
        if ($attribution && $attribution->source_method !== MarketingAttribution::METHOD_DIRECT) {
            return;
        }

        Yii::$app->db->createCommand()->update(
            MarketingAttribution::tableName(),
            [
                'survey_source' => $surveyKey,
                'survey_at' => new \yii\db\Expression('NOW()'),
            ],
            ['user_id' => $userId]
        )->execute();

        $this->logTouch($userId, MarketingTouch::EVENT_SURVEY_ANSWERED, $surveyKey, null, MarketingAttribution::METHOD_SURVEY);
    }

    /**
     * Police.payment_status 0->1 ga o'tgan nuqtada chaqiriladi
     * (PoliceController::actionCheck()/actionGross()). Takroriy chaqiruv
     * (ikkinchi cron yurishi) marketing_touch.police_id'dagi UNIQUE
     * indeks tufayli xavfsiz no-op bo'ladi.
     */
    public function recordPurchase(Police $police): void
    {
        if (!$police->user_id) {
            return;
        }

        $attribution = MarketingAttribution::findOne(['user_id' => $police->user_id]);

        $region = $this->resolveRegion($police);
        $commissionPercent = $region ? MarketingCommissionRate::findPercent((int)$police->provider_id, $region) : null;

        $metadata = [
            'region' => $region,
            'commission_percent' => $commissionPercent,
        ];

        try {
            Yii::$app->db->createCommand()->insert(MarketingTouch::tableName(), [
                'user_id' => $police->user_id,
                'event' => MarketingTouch::EVENT_PURCHASE,
                'source' => $attribution->last_source ?? null,
                'campaign' => $attribution->last_campaign ?? null,
                'method' => $attribution->source_method ?? null,
                'police_id' => $police->id,
                'metadata' => json_encode($metadata),
                'created_at' => new \yii\db\Expression('NOW()'),
            ])->execute();
        } catch (\yii\db\IntegrityException $e) {
            // police_id UNIQUE — bu polis uchun konversiya allaqachon yozilgan, no-op.
        }
    }

    private function resolveRegion(Police $police): ?string
    {
        $vehicle = $police->vehicles[0] ?? null;
        if (!$vehicle || !$vehicle->licenseNumber) {
            return null;
        }

        $isTashkent = (new OsagoRequestBuilder())->isTashkentPlate($vehicle->licenseNumber);

        return $isTashkent ? MarketingCommissionRate::REGION_TASHKENT : MarketingCommissionRate::REGION_OTHER;
    }

    private function upsertAttribution(int $userId, string $source, ?string $campaign, string $method): void
    {
        Yii::$app->db->createCommand()->upsert(
            MarketingAttribution::tableName(),
            [
                'user_id' => $userId,
                'first_source' => $source,
                'first_campaign' => $campaign,
                'last_source' => $source,
                'last_campaign' => $campaign,
                'source_method' => $method,
                'first_seen_at' => new \yii\db\Expression('NOW()'),
                'last_seen_at' => new \yii\db\Expression('NOW()'),
            ],
            [
                'last_source' => $source,
                'last_campaign' => $campaign,
                'source_method' => $method,
                'last_seen_at' => new \yii\db\Expression('NOW()'),
            ]
        )->execute();
    }

    private function logTouch(int $userId, string $event, ?string $source, ?string $campaign, ?string $method): void
    {
        Yii::$app->db->createCommand()->insert(MarketingTouch::tableName(), [
            'user_id' => $userId,
            'event' => $event,
            'source' => $source,
            'campaign' => $campaign,
            'method' => $method,
            'created_at' => new \yii\db\Expression('NOW()'),
        ])->execute();
    }

    private function resolveUserId(string $chatId): ?int
    {
        $id = Botuser::find()->select('id')->where(['chat_id' => $chatId])->scalar();
        return $id ? (int)$id : null;
    }
}
