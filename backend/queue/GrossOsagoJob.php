<?php

namespace backend\queue;

use backend\controllers\BotController;
use backend\gross\GrossOsago;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class GrossOsagoJob extends BaseObject implements JobInterface
{
    /** @var array $policyData full.php dagi $policy_data strukturasiga mos keladi */
    public array $policyData = [];
    public int   $attempt = 1;
    public int   $maxAttempts = 3;

    public function __construct(array $data)
    {
        $this->policyData = $data;
    }

//    public function execute($queue): void
//    {
//        $grossCfg = Yii::$app->params['gross'];
//
//        $service = new GrossOsago([
//            'login'        => $grossCfg['login'],
//            'password'     => $grossCfg['password'],
//            'sender_pinfl' => $grossCfg['senderPinfl'],
//            'marka_id'     => $grossCfg['markaId'] ?? 13,
//            'openai_key'   => Yii::$app->params['openai']['apiKey'],
//            'response_dir' => isset($grossCfg['responseDir'])
//                ? Yii::getAlias($grossCfg['responseDir'])
//                : Yii::getAlias('@runtime/gross'),
//        ]);
//
//        $result = $service->run($this->policyData);
//
//
//        var_dump($result);
//
//        Yii::info(sprintf(
//            'OSAGO created | anketa: %s | premium: %s so\'m | click: %s | payme: %s',
//            $result['anketa_id'],
//            number_format($result['premium']),
//            $result['click_url'] ?? '-',
//            $result['payme_url'] ?? '-',
//        ), 'gross.osago');
//    }

    public function execute($queue): void
    {
        Yii::info("GrossOsagoJob (attempt: {$this->attempt}, pid: " . getmypid() . ") - Started", 'gross');

        try {
            $grossCfg = Yii::$app->params['gross'];
            $service = new GrossOsago([
                'login'        => $grossCfg['login'],
                'password'     => $grossCfg['password'],
                'sender_pinfl' => $grossCfg['senderPinfl'],
                'marka_id'     => $grossCfg['markaId'] ?? 13,
                'openai_key'   => Yii::$app->params['openai']['apiKey'],
                'response_dir' => isset($grossCfg['responseDir'])
                    ? Yii::getAlias($grossCfg['responseDir'])
                    : Yii::getAlias('@runtime/gross'),
            ]);
            $result = $service->run($this->policyData);

            Yii::info("GrossOsagoJob - Muvaffaqiyatli: " . json_encode($result), 'gross');
            $this->sendMessageAdmin("GrossOsagoJob - Muvaffaqiyatli: " . json_encode($result, JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            Yii::warning(
                "GrossOsagoJob (attempt: {$this->attempt}/{$this->maxAttempts}) - Xato: " . $e->getMessage(),
                'gross'
            );

            $this->sendMessageAdmin("GrossOsagoJob (attempt: {$this->attempt}/{$this->maxAttempts}) - Xato: " . json_encode($e->getMessage(), JSON_PRETTY_PRINT));

            if ($this->attempt < $this->maxAttempts) {
                $retryJob          = clone $this;
                $retryJob->attempt = $this->attempt + 1;

                Yii::$app->grossQueue->delay(10)->push($retryJob);

                Yii::info(
                    "GrossOsagoJob - 10s dan keyin qayta yuborildi (attempt: {$retryJob->attempt})",
                    'gross'
                );


                $this->sendMessageAdmin("GrossOsagoJob - 10s dan keyin qayta yuborildi (attempt: {$retryJob->attempt})");
            } else {
                Yii::error(
                    "GrossOsagoJob - {$this->maxAttempts} ta urinishdan so'ng muvaffaqiyatsiz: " . $e->getMessage(),
                    'gross'
                );
                $this->sendMessageAdmin("GrossOsagoJob - {$this->maxAttempts} ta urinishdan so'ng muvaffaqiyatsiz: " . json_encode($e->getMessage(), JSON_PRETTY_PRINT));
            }
        }
    }

    private function sendMessage($chatId, $text)
    {
        $telegram = Yii::$app->telegram;
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }

    private function sendMessageAdmin($text)
    {
        $this->sendMessage(BotController::ADMIN_ID, $text);
    }
}