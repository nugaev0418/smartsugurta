<?php

namespace console\controllers;

use backend\uzbekinvest\UzbekInvestClient;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Uzbekinvest ulanishini tekshirish (polisa YARATMAYDI):
 *   php yii uzbek-invest/login-test [profil=queue]
 *
 * Worker qaysi foydalanuvchi nomidan ishlasa, shu foydalanuvchi bilan ishga tushiring
 * (masalan: sudo -u www-data php yii uzbek-invest/login-test).
 */
class UzbekInvestController extends Controller
{
    public function actionLoginTest(string $profile = 'queue'): int
    {
        $cfg = Yii::$app->params['uzbekinvest'] ?? null;
        if (!$cfg || empty($cfg['login'])) {
            $this->stderr("params['uzbekinvest'] topilmadi\n");

            return ExitCode::CONFIG;
        }

        $dir = \backend\uzbekinvest\UzbekInvestOsago::sharedStorageDir();
        $this->stdout("Papka: {$dir}\n");
        $this->stdout('Foydalanuvchi: ' . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n");
        foreach (['cookies.txt', 'session.txt'] as $f) {
            $p = "{$dir}/{$f}";
            $this->stdout(sprintf("  %s: %s\n", $f, is_file($p) ? (is_writable($p) ? 'bor, yoziladi' : 'bor, YOZIB BO\'LMAYDI') : "yo'q"));
        }

        try {
            $client = new UzbekInvestClient(
                $cfg['login'],
                $cfg['password'],
                $dir,
                $cfg['baseUrl'] ?? 'https://sales-platform.uzbekinvest.uz',
                (bool)($cfg['verifySsl'] ?? true)
            );
            $session = $client->getSession();
            $this->stdout('Login OK (sessiya uzunligi ' . strlen($session) . ")\n");

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $this->stderr('XATO: ' . $e->getMessage() . "\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
