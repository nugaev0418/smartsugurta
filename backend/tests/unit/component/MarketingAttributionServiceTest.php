<?php

namespace backend\tests\unit\component;

use backend\component\MarketingAttributionService;
use common\models\Botuser;
use common\models\MarketingAttribution;
use common\models\MarketingTouch;
use common\models\Police;
use Yii;

/**
 * DB'ga haqiqatan ulanadigan test — backend/tests/unit/_bootstrap.php o'z
 * ichiga Yii2 module/'db' komponentini OLMAYDI (ataylab, boshqa pure-unit
 * testlar DB'siz ishlashi uchun), shuning uchun bu klass _before()'da
 * common/config/test.php + test-local.php orqali TEST bazasiga ('db_test',
 * `yii2advanced_test` — hech qachon asosiy 'smart' bazasiga emas) alohida
 * ulanadi. Test bazasi mavjud bo'lib, migratsiyalar qo'llangan bo'lishi
 * kerak (`YII_ENV=test php yii_test migrate`); aks holda har bir test
 * markTestSkipped() bilan xavfsiz o'tkazib yuboriladi — yolg'on "muvaffaqiyat"
 * ko'rsatilmaydi.
 *
 * Har bir test o'z yozuvlarini tozalaydi (tearDown), lekin baza umumiy
 * bo'lgani uchun parallel test ishga tushirishdan saqlanish kerak.
 */
class MarketingAttributionServiceTest extends \Codeception\Test\Unit
{
    private MarketingAttributionService $service;
    private string $chatId;
    private ?int $userId = null;

    protected function _before()
    {
        if (!Yii::$app->has('db')) {
            $root = dirname(__DIR__, 4);
            // yii_test'dagi bilan bir xil tartib (main -> main-local -> test
            // -> test-local) — faqat shundagina 'db'ning 'class'i (main-local)
            // va test bazasi dsn'i (test/test-local) to'g'ri birlashadi.
            $config = \yii\helpers\ArrayHelper::merge(
                require $root . '/common/config/main.php',
                require $root . '/common/config/main-local.php',
                require $root . '/common/config/test.php',
                file_exists($root . '/common/config/test-local.php')
                    ? require $root . '/common/config/test-local.php'
                    : []
            );
            Yii::$app->set('db', $config['components']['db']);
        }

        try {
            Yii::$app->db->open();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Test bazasi mavjud emas/ulanmadi: ' . $e->getMessage());
        }

        if (!Yii::$app->db->schema->getTableSchema('{{%marketing_attribution}}', true)) {
            $this->markTestSkipped("Test bazasida migratsiyalar qo'llanmagan (marketing_attribution jadvali yo'q).");
        }

        $this->service = new MarketingAttributionService();
        $this->chatId = '900000' . random_int(1000, 9999);

        $botuser = new Botuser();
        $botuser->chat_id = $this->chatId;
        $botuser->referral_code = Botuser::generateReferralCode();
        $botuser->save(false);
        $this->userId = $botuser->id;
    }

    protected function _after()
    {
        if (!$this->userId) {
            return;
        }
        MarketingTouch::deleteAll(['user_id' => $this->userId]);
        MarketingAttribution::deleteAll(['user_id' => $this->userId]);
        Botuser::deleteAll(['id' => $this->userId]);
    }

    public function testFirstSourceIsNeverOverwrittenByLaterDirectTouch()
    {
        $this->service->recordDirectTouch($this->chatId, true);
        $this->service->recordDirectTouch($this->chatId, false);

        $attribution = MarketingAttribution::findOne(['user_id' => $this->userId]);
        $this->assertNotNull($attribution);
        $this->assertSame(MarketingAttribution::METHOD_DIRECT, $attribution->first_source);
        $this->assertSame(MarketingAttribution::METHOD_DIRECT, $attribution->source_method);
    }

    public function testSurveyAnswerIsIgnoredWhenSourceAlreadyKnown()
    {
        $this->service->recordReferralTouch($this->chatId, true);
        $this->service->recordSurveyAnswer($this->chatId, 'instagram_blogger');

        $attribution = MarketingAttribution::findOne(['user_id' => $this->userId]);
        $this->assertNull($attribution->survey_source);
    }

    public function testSurveyAnswerIsRecordedForDirectUser()
    {
        $this->service->recordDirectTouch($this->chatId, true);
        $this->service->recordSurveyAnswer($this->chatId, 'friend_referral');

        $attribution = MarketingAttribution::findOne(['user_id' => $this->userId]);
        $this->assertSame('friend_referral', $attribution->survey_source);

        $touchCount = MarketingTouch::find()
            ->where(['user_id' => $this->userId, 'event' => MarketingTouch::EVENT_SURVEY_ANSWERED])
            ->count();
        $this->assertSame(1, (int)$touchCount);
    }

    public function testRecordPurchaseIsIdempotentForSamePolice()
    {
        $this->service->recordDirectTouch($this->chatId, true);

        $police = new Police();
        $police->user_id = $this->userId;
        $police->amount = 100000;
        $police->provider_id = Police::PROVIDER_GROSS;
        $police->status = 1;
        $police->payment_status = 1;
        $police->save(false);

        $this->service->recordPurchase($police);
        $this->service->recordPurchase($police); // takroriy chaqiruv — no-op bo'lishi kerak

        $purchaseCount = MarketingTouch::find()
            ->where(['user_id' => $this->userId, 'event' => MarketingTouch::EVENT_PURCHASE])
            ->count();
        $this->assertSame(1, (int)$purchaseCount);

        Police::deleteAll(['id' => $police->id]);
    }
}
