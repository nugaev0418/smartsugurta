<?php

namespace console\controllers;

use backend\component\insurance\PolicePaymentChecker;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * To'lov kutilayotgan polislarni provayder bo'yicha tekshiradi (ilgari cron
 * `curl https://.../police/check|gross|uzbekinvest` orqali veb'dan chaqirilardi).
 *
 *   php yii police-check/eai
 *   php yii police-check/gross
 *   php yii police-check/uzbekinvest
 *
 * Har daqiqada `deploy/systemd/police-check@.timer` orqali ishga tushiriladi.
 * Bir xil tekshiruv ikki marta parallel ishlamasligi uchun mutex olinadi
 * (systemd oneshot ham ustma-ust ishga tushirmaydi — bu qo'shimcha himoya,
 * masalan qo'lda ishga tushirilganda).
 */
class PoliceCheckController extends Controller
{
    public function actionEai(): int
    {
        return $this->runCheck('eai', fn(PolicePaymentChecker $c) => $c->checkEai());
    }

    public function actionGross(): int
    {
        return $this->runCheck('gross', fn(PolicePaymentChecker $c) => $c->checkGross());
    }

    public function actionUzbekinvest(): int
    {
        return $this->runCheck('uzbekinvest', fn(PolicePaymentChecker $c) => $c->checkUzbekinvest());
    }

    private function runCheck(string $provider, callable $check): int
    {
        $lock = "police-check-{$provider}";
        if (!Yii::$app->mutex->acquire($lock)) {
            $this->stdout("[{$provider}] oldingi tekshiruv hali ishlayapti, o'tkazib yuborildi\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        try {
            $checker = new PolicePaymentChecker(function (string $line) use ($provider) {
                $this->stdout('[' . date('Y-m-d H:i:s') . "] [{$provider}] {$line}\n");
            });

            $activated = $check($checker);
            if ($activated > 0) {
                $this->stdout("[{$provider}] faollashtirildi: {$activated} ta polis\n", Console::FG_GREEN);
            }

            return ExitCode::OK;
        } catch (\Throwable $e) {
            Yii::error("police-check/{$provider}: " . $e->getMessage(), 'police-check');
            $this->stderr("[{$provider}] XATO: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            Yii::$app->mutex->release($lock);
        }
    }
}
