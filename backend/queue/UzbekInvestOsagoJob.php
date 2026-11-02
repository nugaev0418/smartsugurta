<?php

namespace backend\queue;

use backend\component\insurance\InsuranceRecordPersister;
use backend\component\insurance\JobRetryService;
use backend\component\insurance\OsagoApplicationData;
use backend\component\insurance\OsagoSubmissionService;
use backend\component\LogChannel;
use backend\models\EuroAsia;
use backend\uzbekinvest\UzbekInvestOsago;
use common\models\Botuser;
use common\models\GrossApiLog;
use common\models\Police;
use common\models\SeasonalInsurance;
use common\models\Setting;
use common\models\Text;
use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;

/**
 * Uzbekinvest (sales-platform.uzbekinvest.uz) orqali polisa yaratadi —
 * GrossOsagoJob bilan bir xil tuzilma: tsikl/urinishlar, Police yozish,
 * foydalanuvchiga to'lov havolasi. Navbat: uzbekInvestQueue
 * (`php yii uzbek-invest-queue/listen`), faqat BITTA worker.
 */
class UzbekInvestOsagoJob extends BaseObject implements RetryableJobInterface
{
    public array $policyData      = [];
    public array $policyDataEAI   = [];
    public array $applicationData = [];
    public int   $chatId          = 0;
    public int   $maxAttempts     = 3;
    public int   $retryDelay      = 10;
    public int   $maxRounds       = 2;
    public int   $roundDelay      = 40;
    private ?string $lastError    = null;

    public function __construct(array $data)
    {
        $this->policyData      = $data['policyData'];
        $this->policyDataEAI   = $data['policyDataEAI'];
        $this->applicationData = $data['applicationData'] ?? [];
        $this->chatId          = (int) $data['chat_id'];
    }

    /** Ish o'z budjetidan oldin majburan o'ldirilmasligi uchun (GrossOsagoJob::getTtr() bilan bir xil sabab). */
    public function getTtr(): int
    {
        return 3600;
    }

    /**
     * Uzilgan ish QAYTA BAJARILMAYDI (dublikat polisa xavfi) — haqiqiy
     * to'xtatishni QueueInterruptionNotifier qiladi. Batafsil: GrossOsagoJob::canRetry().
     */
    public function canRetry($attempt, $error): bool
    {
        return $attempt < 2;
    }

    public function execute($queue): void
    {
        // Navbatda turgan ariza pauza boshlanishidan oldin qo'yilgan bo'lsa: bot Uzbekinvest'ga kirmaydi,
        // shuning uchun ariza Gross'ga o'tkaziladi (foydalanuvchi kutib qolmasin).
        if (Setting::isUzbekinvestPaused() && $this->rerouteToGross()) {
            return;
        }

        for ($round = 1; $round <= $this->maxRounds; $round++) {
            Yii::info("UzbekInvestOsagoJob (pid: " . getmypid() . ") - {$round}/{$this->maxRounds}-tsikl boshlandi", 'uzbekinvest');

            if ($this->tryUzbekInvest()) {
                return;
            }

            $this->sendMessageAdmin("⚠️ Uzbekinvest {$this->maxAttempts} marta muvaffaqiyatsiz ({$round}/{$this->maxRounds}-tsikl).");

            if ($round < $this->maxRounds) {
                $this->sendMessageAdmin("🔁 {$round}/{$this->maxRounds}-tsikl muvaffaqiyatsiz. {$this->roundDelay}s dan keyin qayta boshlanadi...");
                sleep($this->roundDelay);
            }
        }

        Yii::error("UzbekInvestOsagoJob: {$this->maxRounds} to'liq tsikldan so'ng ham muvaffaqiyatsiz (chatId: {$this->chatId})", 'uzbekinvest');
        $this->sendMessageAdmin("🆘 UzbekInvestOsagoJob: {$this->maxRounds} to'liq tsikldan so'ng ham muvaffaqiyatsiz! Qo'lda tekshiruv talab qilinadi.");

        // Admin online bo'lsa unga qayta urinish taklif qilinadi (foydalanuvchi kutadi);
        // aks holda foydalanuvchiga darhol uzilish xabari yuboriladi.
        $offered = (new JobRetryService())->offerRetry(
            $this->applicationData,
            $this->chatId,
            Police::PROVIDER_UZBEKINVEST,
            (string)$this->lastError,
            'uzbekInvestQueue'
        );
        if (!$offered) {
            $this->sendMessage($this->chatId, JobRetryService::userFailureMessage());
        }
    }

    /** Arizani Gross navbatiga o'tkazadi (pauza paytida). Muvaffaqiyatli bo'lsa true. */
    private function rerouteToGross(): bool
    {
        try {
            $botuser = Botuser::find()->where(['chat_id' => $this->chatId])->one();
            if (!$botuser || !$this->applicationData) {
                return false;
            }

            $jobId = Yii::createObject(OsagoSubmissionService::class)->queueForProvider(
                OsagoApplicationData::fromArray($this->applicationData),
                $botuser,
                Police::PROVIDER_GROSS
            );
            $this->sendMessageAdmin("⏸ Uzbekinvest pauzada: ariza Gross navbatiga o'tkazildi (job " . json_encode($jobId) . ", chat {$this->chatId}).");

            return true;
        } catch (\Throwable $e) {
            Yii::error('Pauzada Gross\'ga o\'tkazib bo\'lmadi: ' . $e->getMessage(), 'uzbekinvest');

            return false;
        }
    }

    private function tryUzbekInvest(): bool
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            $runCompleted = false; // true bo'lgach Uzbekinvest'da polis yaratilgan: Gross'ga o'tkazish dublikat bo'lardi
            try {
                Yii::info("Uzbekinvest urinish {$attempt}/{$this->maxAttempts}", 'uzbekinvest');

                // Uzbekinvest'da polis yaratishdan OLDIN barcha lokal shartlarni tekshiramiz:
                // keyin chiqadigan xato urinishni takrorlatib, dublikat polis yaratardi.
                $botuser = Botuser::find()->where(['chat_id' => $this->chatId])->one();
                if (!$botuser) {
                    throw new \RuntimeException("Botuser topilmadi (chat_id: {$this->chatId})");
                }

                $season = SeasonalInsurance::find()
                    ->where(['seasonId' => $this->policyDataEAI['seasonalInsuranceId']])
                    ->one();
                if (!$season) {
                    throw new \RuntimeException("SeasonalInsurance topilmadi (seasonId: {$this->policyDataEAI['seasonalInsuranceId']})");
                }

                $result = UzbekInvestOsago::fromParams('queue', $this->chatId)->run($this->policyData);
                $runCompleted = true;

                [$gateway, $paymentUrl] = $this->pickPayment($result['pay_urls'] ?? []);
                if ($paymentUrl === null) {
                    // Polis Uzbekinvest'da allaqachon saqlangan: qayta yuborish dublikat
                    // yaratadi, shuning uchun urinishni takrorlamaymiz — qo'lda hal qilinadi.
                    Yii::error("Uzbekinvest: to'lov havolasi topilmadi (shartnoma {$result['contract_id']})", 'uzbekinvest');
                    $this->sendMessageAdmin(
                        "🆘 Uzbekinvest polisi yaratildi (shartnoma {$result['contract_id']}, chat {$this->chatId}), lekin to'lov havolasi topilmadi. Qo'lda tekshiring."
                    );
                    $this->sendMessage(
                        $this->chatId,
                        "Arizangiz qabul qilindi, to'lov havolasini tez orada yuboramiz. Savollar bo'lsa operator bilan bog'laning."
                    );

                    return true;
                }

                Yii::info("Uzbekinvest muvaffaqiyatli: shartnoma {$result['contract_id']}", 'uzbekinvest');
                $this->sendMessageAdmin(
                    "✅ Uzbekinvest muvaffaqiyatli (urinish {$attempt}/{$this->maxAttempts}):\n"
                    . "shartnoma: {$result['contract_id']}, mukofot: {$result['premium']}"
                );

                $police                    = new Police();
                // ersp.e-osgo.uz UUID'i polis to'langanidan keyingina ma'lum bo'ladi —
                // PoliceController::actionUzbekinvest() uni shu yerga yozadi.
                $police->policeId          = 'UZB-' . $result['contract_id'];
                $police->user_id           = $botuser->id;
                $police->startAt           = date('Y-m-d', strtotime($this->policyDataEAI['startAt']));
                $police->paymentLink       = $paymentUrl;
                $police->paymentId         = 0;
                $police->gateway           = $gateway;
                $police->amount            = (int) $result['premium'];
                $police->driverRestriction = $this->policyDataEAI['driverRestriction'];
                $police->season_id         = $season->id;
                $police->anketa_id         = (int) $result['contract_id'];
                $police->provider_id       = Police::PROVIDER_UZBEKINVEST;
                $police->save(false);

                // Job logs'dagi shu urinish yozuvlarini yaratilgan polisaga bog'laymiz.
                if (!empty($result['run_id'])) {
                    GrossApiLog::updateAll(['police_id' => $police->id], ['run_id' => $result['run_id']]);
                }

                // Polis yaratildi va saqlandi — keyingi xatolik urinishni QAYTA
                // boshlamasligi kerak (Uzbekinvest'ga ikkinchi polis ketardi).
                try {
                    (new InsuranceRecordPersister())->persist($police, $this->applicationData);
                } catch (\Throwable $persistError) {
                    Yii::error("InsuranceRecordPersister xato (police #{$police->id}): " . $persistError->getMessage(), 'uzbekinvest');
                    $this->sendMessageAdmin("⚠️ Polis #{$police->id} uchun vehicle/owner/driver saqlanmadi: " . $persistError->getMessage());
                }

                // Polis allaqachon yaratilgan: xabar yuborishdagi xato qayta urinishga olib kelmasin.
                try {
                    $text = $this->getInsuranceReadyPaymentText($botuser, $police, $gateway, $paymentUrl);
                    $this->sendMessageWithPaymentButton($this->chatId, $text, $gateway, $paymentUrl);
                } catch (\Throwable $notifyError) {
                    Yii::error("Uzbekinvest: foydalanuvchiga xabar yuborilmadi (police #{$police->id}): " . $notifyError->getMessage(), 'uzbekinvest');
                    $this->sendMessageAdmin("⚠️ Polis #{$police->id} (chat {$this->chatId}) yaratildi, lekin to'lov havolasi foydalanuvchiga yuborilmadi: " . $notifyError->getMessage());
                }

                return true;
            } catch (\Throwable $e) {
                $lastException = $e;

                // Ish jarayonida pauza yoqilgan va polis hali yaratilmagan: kutmasdan Gross'ga o'tkazamiz.
                if (!$runCompleted && Setting::isUzbekinvestPaused() && $this->rerouteToGross()) {
                    return true;
                }

                Yii::warning("Uzbekinvest urinish {$attempt}/{$this->maxAttempts} xato: " . $e->getMessage(), 'uzbekinvest');

                if ($attempt < $this->maxAttempts) {
                    $this->sendMessageAdmin(
                        "⚠️ Uzbekinvest urinish {$attempt}/{$this->maxAttempts} xato:\n"
                        . $e->getMessage()
                        . "\n{$this->retryDelay}s dan keyin qayta uriniladi..."
                    );
                    sleep($this->retryDelay);
                }
            }
        }

        $this->lastError = $lastException->getMessage();
        Yii::error("Uzbekinvest {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz: " . $lastException->getMessage(), 'uzbekinvest');
        $this->sendMessageAdmin(
            "❌ Uzbekinvest {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz:\n"
            . $lastException->getMessage()
        );

        return false;
    }

    /**
     * Foydalanuvchi tanlagan to'lov tizimi bo'yicha havolani tanlaydi;
     * bo'lmasa mavjud Payme/Click/Uzum/Paynet'dan birinchisini oladi.
     *
     * @return array{0: string, 1: ?string} [gateway nomi, havola]
     */
    private function pickPayment(array $payUrls): array
    {
        $wanted = strtolower((string) ($this->policyDataEAI['billingGateway'] ?? ''));
        if ($wanted !== '' && !empty($payUrls[$wanted])) {
            return [strtoupper($wanted), $payUrls[$wanted]];
        }
        foreach (['payme', 'click', 'uzum', 'paynet'] as $name) {
            if (!empty($payUrls[$name])) {
                return [strtoupper($name), $payUrls[$name]];
            }
        }

        return [strtoupper($wanted), null];
    }

    private function getUserLang(Botuser $user): string
    {
        if ($user->data) {
            $data = json_decode($user->data, true);
            return $data['lang'] ?? 'uz';
        }
        return 'uz';
    }

    private function getInsuranceReadyPaymentText(Botuser $user, Police $police, string $gateway, string $paymentUrl): string
    {
        $lang        = $this->getUserLang($user);
        $record      = Text::findOne(['keyword' => 'insurance_ready_payment']);
        $gatewayName = ucfirst(strtolower($gateway));

        if ($record && $record->$lang) {
            return sprintf($record->$lang, $police->id, $gatewayName, $paymentUrl);
        }

        return $lang === 'ru'
            ? sprintf(
                "ID: %s <b>Ваша страховка готова! Перейдите по ссылке ниже, чтобы оплатить через %s.</b> \n<a href='%s'>👉 Оплата</a>",
                $police->id, $gatewayName, $paymentUrl
            )
            : sprintf(
                "ID: %s <b>Sug'urtangiz tayyor! Quyidagi havola orqali %s orqali to'lovni amalga oshiring.</b> \n<a href='%s'>👉 To'lov</a>",
                $police->id, $gatewayName, $paymentUrl
            );
    }

    private function paymentButtonLabel(string $gateway): string
    {
        return match (strtoupper($gateway)) {
            EuroAsia::GATEWAY_PAYME => "Payme bilan to'lash",
            EuroAsia::GATEWAY_CLICK => "Click bilan to'lash",
            default => ucfirst(strtolower($gateway)) . " bilan to'lash",
        };
    }

    private function sendMessageWithPaymentButton(string $chatId, string $text, string $gateway, string $paymentUrl): void
    {
        $button   = Yii::$app->telegram->buildInlineKeyboardButton($this->paymentButtonLabel($gateway), $paymentUrl);
        $keyboard = Yii::$app->telegram->buildInlineKeyBoard([[$button]]);

        Yii::$app->telegram->sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => $keyboard,
        ]);
    }

    private function sendMessage(string $chatId, string $text): void
    {
        Yii::$app->telegram->sendMessage([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    /** Job holati logi: log kanaliga (Polis yaratilmadi xabari alohida, JobRetryService orqali adminga boradi). */
    private function sendMessageAdmin(string $text): void
    {
        LogChannel::send($text);
    }
}
