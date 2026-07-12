<?php

namespace backend\queue;

use backend\component\EuroAsiaService;
use backend\controllers\BotController;
use backend\gross\GrossOsago;
use common\models\Botuser;
use common\models\Police;
use common\models\SeasonalInsurance;
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

    public function __construct(array $data)
    {
        $this->policyDataGross = $data['policyDataGross'];
        $this->policyDataEAI   = $data['policyDataEAI'];
        $this->chatId          = (int) $data['chat_id'];
    }

    public function execute($queue): void
    {
        Yii::info("GrossOsagoJob (pid: " . getmypid() . ") - Started", 'gross');

        if ($this->tryGross()) {
            return;
        }

        $this->sendMessageAdmin("⚠️ Gross {$this->maxAttempts} marta muvaffaqiyatsiz. EuroAsia orqali urinilmoqda...");


        $this->tryEuroAsia();
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
                $police->paymentLink       = "CLICK: {$result['click_url']}\nPayme: {$result['payme_url']}";
                $police->paymentId         = 0;
                $police->gateway           = $this->policyDataEAI['billingGateway'];
                $police->amount            = $result['premium'];
                $police->driverRestriction = $this->policyDataEAI['driverRestriction'];
                $police->season_id         = $season->id;
                $police->anketa_id         = $result['anketa_id'];
                $police->provider_id       = Police::PROVIDER_GROSS;
                $police->save(false);


                $text = sprintf(
                    "ID: %s <b>Sug'urtangiz tayyor! Pastdagi havola orqali to'lovni amalga oshiring.\n❗Agar Click orqali to'lov amalga oshmasa, Payme orqali to'lov qilishingiz mumkin.\n\nВаша страховка готова! Перейдите по ссылке ниже, чтобы произвести оплату.\n❗Если оплата через Click не проходит, вы можете оплатить через Payme.</b> \n<a href='%s'>👉 Payme</a>\n<a href='%s'>👉 Click</a>"
                    ,
                    $police->id,
                    $result['payme_url'],
                    $result['click_url']
                );

                $this->sendMessage($this->chatId, $text);


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

    private function tryEuroAsia(): void
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

                $this->sendMessage($this->chatId, $text);

                return;

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
            . "\n\n🆘 Qo'lda tekshiruv talab qilinadi!"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────

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