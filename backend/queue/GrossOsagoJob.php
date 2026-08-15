<?php

namespace backend\queue;

use backend\component\EuroAsiaService;
use backend\controllers\BotController;
use backend\gross\GrossOsago;
use backend\models\EuroAsia;
use common\models\Botuser;
use common\models\Police;
use common\models\SeasonalInsurance;
use common\models\Text;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class GrossOsagoJob extends BaseObject implements JobInterface
{
    public array  $policyDataGross = [];
    public array  $policyDataEAI   = [];
    public int    $chatId          = 0;
    public int    $maxAttempts     = 3;
    public int    $retryDelay      = 10;
    public int    $maxRounds       = 2;
    public int    $roundDelay      = 40;

    public function __construct(array $data)
    {
        $this->policyDataGross = $data['policyDataGross'];
        $this->policyDataEAI   = $data['policyDataEAI'];
        $this->chatId          = (int) $data['chat_id'];
    }

    public function execute($queue): void
    {
        for ($round = 1; $round <= $this->maxRounds; $round++) {
            Yii::info("GrossOsagoJob (pid: " . getmypid() . ") - {$round}/{$this->maxRounds}-tsikl boshlandi", 'gross');

            if ($this->tryGross()) {
                return;
            }

            $this->sendMessageAdmin("⚠️ Gross {$this->maxAttempts} marta muvaffaqiyatsiz ({$round}/{$this->maxRounds}-tsikl). EuroAsia orqali urinilmoqda...");

            if ($this->tryEuroAsia()) {
                return;
            }

            if ($round < $this->maxRounds) {
                $this->sendMessageAdmin("🔁 {$round}/{$this->maxRounds}-tsikl: Gross va EuroAsia ikkalasi ham muvaffaqiyatsiz. {$this->roundDelay}s dan keyin butun tsikl qayta boshlanadi...");
                sleep($this->roundDelay);
            }
        }

        Yii::error("GrossOsagoJob: {$this->maxRounds} to'liq tsikldan so'ng ham muvaffaqiyatsiz (chatId: {$this->chatId})", 'gross');
        $this->sendMessageAdmin("🆘 GrossOsagoJob: {$this->maxRounds} to'liq tsikldan so'ng ham muvaffaqiyatsiz! Qo'lda tekshiruv talab qilinadi.");

        $this->sendMessage(
            $this->chatId,
            "Sug'urta kompaniyasi xizmatlarida uzilish bo'lmoqda, iltimos keyinroq qayta urinib ko'ring, noqulaylik uchun uzur so'raymiz!"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GROSS
    // ─────────────────────────────────────────────────────────────────────────

    private function tryGross(): bool
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                Yii::info("Gross urinish {$attempt}/{$this->maxAttempts}", 'gross');

                $grossCfg = Yii::$app->params['gross'];
                $service  = new GrossOsago([
                    'login'        => $grossCfg['login'],
                    'password'     => $grossCfg['password'],
                    'sender_pinfl' => $grossCfg['senderPinfl'],
                    'marka_id'     => $grossCfg['markaId'] ?? 13,
                    'openai_key'   => Yii::$app->params['openai']['apiKey'],
                    'response_dir' => isset($grossCfg['responseDir'])
                        ? Yii::getAlias($grossCfg['responseDir'])
                        : Yii::getAlias('@runtime/gross'),
                ]);

                $result = $service->run($this->policyDataGross);

                Yii::info("Gross muvaffaqiyatli: " . json_encode($result), 'gross');
                $this->sendMessageAdmin(
                    "✅ Gross muvaffaqiyatli (urinish {$attempt}/{$this->maxAttempts}):\n"
                    . json_encode($result, JSON_PRETTY_PRINT)
                );


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

                $police                    = new Police();
                $police->policeId          = $result['uuid'];
                $police->user_id           = $botuser->id;
                $police->startAt           = date('Y-m-d', strtotime($this->policyDataEAI['startAt']));
                $police->paymentLink       = $result['payment_url'];
                $police->paymentId         = 0;
                $police->gateway           = $result['gateway'];
                $police->amount            = $result['premium'];
                $police->driverRestriction = $this->policyDataEAI['driverRestriction'];
                $police->season_id         = $season->id;
                $police->anketa_id         = $result['anketa_id'];
                $police->provider_id       = Police::PROVIDER_GROSS;
                $police->save(false);


                $text = $this->getInsuranceReadyPaymentText($botuser, $police, $result);

                $this->sendMessageWithPaymentButton($this->chatId, $text, $result['gateway'], $result['payment_url']);


                return true;

            } catch (\Throwable $e) {
                $lastException = $e;

                Yii::warning("Gross urinish {$attempt}/{$this->maxAttempts} xato: " . $e->getMessage(), 'gross');

                if ($attempt < $this->maxAttempts) {
                    $this->sendMessageAdmin(
                        "⚠️ Gross urinish {$attempt}/{$this->maxAttempts} xato:\n"
                        . $e->getMessage()
                        . "\n{$this->retryDelay}s dan keyin qayta uriniladi..."
                    );
                    sleep($this->retryDelay);
                }
            }
        }

        Yii::error("Gross {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz: " . $lastException->getMessage(), 'gross');
        $this->sendMessageAdmin(
            "❌ Gross {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz:\n"
            . $lastException->getMessage()
        );

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EUROASIA FALLBACK
    // ─────────────────────────────────────────────────────────────────────────

    private function tryEuroAsia(): bool
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                Yii::info("EuroAsia urinish {$attempt}/{$this->maxAttempts}", 'gross');

                $eai = new EuroAsiaService();
                $dto = $eai->createOsagoDTO($this->policyDataEAI);

                if (!$dto->success) {
                    throw new \RuntimeException("EuroAsia DTO xato: " . ($dto->error ?? "Noma'lum xato"));
                }

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

                $police                    = new Police();
                $police->policeId          = $dto->policyId;
                $police->user_id           = $botuser->id;
                $police->startAt           = date('Y-m-d', strtotime($this->policyDataEAI['startAt']));
                $police->paymentLink       = $dto->paymentLink;
                $police->paymentId         = $dto->paymentId;
                $police->gateway           = $this->policyDataEAI['billingGateway'];
                $police->amount            = 64000;
                $police->driverRestriction = $this->policyDataEAI['driverRestriction'];
                $police->season_id         = $season->id;
                $police->provider_id       = Police::PROVIDER_EAI;
                $police->save(false);

                Yii::info("EuroAsia muvaffaqiyatli: policeId={$dto->policyId}", 'gross');
                $this->sendMessageAdmin(
                    "✅ EuroAsia muvaffaqiyatli (urinish {$attempt}/{$this->maxAttempts}):\n"
                    . "policeId: {$dto->policyId}\n"
                    . "paymentLink: {$dto->paymentLink}"
                );

                $text = sprintf(
                    "ID: %s <b>Sug'urtangiz tayyor! Pastdagi havola orqali o'tib to'lovni amalga oshiring.\n\nВаша страховка готова! Перейдите по ссылке ниже, чтобы произвести оплату.</b> \n<a href='%s'>👉 To'lov / Оплата</a>"
                    ,
                    $police->id,
                    $dto->paymentLink
                );

                $this->sendMessageWithPaymentButton($this->chatId, $text, $this->policyDataEAI['billingGateway'], $dto->paymentLink);

                return true;

            } catch (\Throwable $e) {
                $lastException = $e;

                Yii::warning("EuroAsia urinish {$attempt}/{$this->maxAttempts} xato: " . $e->getMessage(), 'gross');

                if ($attempt < $this->maxAttempts) {
                    $this->sendMessageAdmin(
                        "⚠️ EuroAsia urinish {$attempt}/{$this->maxAttempts} xato:\n"
                        . $e->getMessage()
                        . "\n{$this->retryDelay}s dan keyin qayta uriniladi..."
                    );
                    sleep($this->retryDelay);
                }
            }
        }

        Yii::error("EuroAsia {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz: " . $lastException->getMessage(), 'gross');
        $this->sendMessageAdmin(
            "❌ EuroAsia ham {$this->maxAttempts} urinishdan so'ng muvaffaqiyatsiz:\n"
            . $lastException->getMessage()
        );

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function getUserLang(Botuser $user): string
    {
        if ($user->data) {
            $data = json_decode($user->data, true);
            return $data['lang'] ?? 'uz';
        }
        return 'uz';
    }

    private function getInsuranceReadyPaymentText(Botuser $user, Police $police, array $result): string
    {
        $lang        = $this->getUserLang($user);
        $record      = Text::findOne(['keyword' => 'insurance_ready_payment']);
        $gatewayName = ucfirst(strtolower($result['gateway']));

        if ($record && $record->$lang) {
            return sprintf($record->$lang, $police->id, $gatewayName, $result['payment_url']);
        }

        return $lang === 'ru'
            ? sprintf(
                "ID: %s <b>Ваша страховка готова! Перейдите по ссылке ниже, чтобы оплатить через %s.</b> \n<a href='%s'>👉 Оплата</a>",
                $police->id, $gatewayName, $result['payment_url']
            )
            : sprintf(
                "ID: %s <b>Sug'urtangiz tayyor! Quyidagi havola orqali %s orqali to'lovni amalga oshiring.</b> \n<a href='%s'>👉 To'lov</a>",
                $police->id, $gatewayName, $result['payment_url']
            );
    }

    private function paymentButtonLabel(string $gateway): string
    {
        return match (strtoupper($gateway)) {
            EuroAsia::GATEWAY_PAYME => "💳 Payme bilan to'lash",
            EuroAsia::GATEWAY_CLICK => "🙂 Click bilan to'lash",
            default => "💳 " . ucfirst(strtolower($gateway)) . " bilan to'lash",
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
            'chat_id' => $chatId,
            'text'    => $text,
            'parse_mode' => 'HTML'
        ]);
    }

    private function sendMessageAdmin(string $text): void
    {
        $this->sendMessage(BotController::ADMIN_ID, $text);
    }
}