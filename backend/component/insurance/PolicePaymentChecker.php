<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\MarketingAttributionService;
use backend\controllers\BotController;
use backend\gross\GrossOsago;
use backend\uzbekinvest\UzbekInvestOsago;
use common\models\Botuser;
use common\models\Income;
use common\models\Police;
use common\models\Setting;
use common\models\Text;
use CURLFile;
use Yii;
use yii\helpers\FileHelper;

/**
 * To'lov kutilayotgan (status=0) polislarni provayder bo'yicha tekshiradi va
 * to'langanlarini faollashtiradi: PDF'ni yuklab foydalanuvchiga va buyurtmalar
 * kanaliga yuboradi, foydalanuvchi/referal bonusini yozadi.
 *
 * Ilgari bu mantiq PoliceController'ning veb action'larida edi va cron `curl`
 * orqali har daqiqa chaqirardi — PHP-FPM worker'larini band qilib, nginx
 * timeout'ida yarim yo'lda uzilardi. Endi faqat konsoldan ishlaydi:
 * `php yii police-check/eai|gross|uzbekinvest` (deploy/systemd/police-check@.timer).
 *
 * Har bir polis alohida try/catch ichida — bittasining xatosi qolganlarini
 * to'xtatmaydi. Takroriy faollashtirishdan Police::claimActivation() himoya qiladi.
 */
class PolicePaymentChecker
{
    /** @var callable(string): void */
    private $log;
    private ?GrossOsago $grossService = null;

    public function __construct(?callable $log = null)
    {
        $this->log = $log ?? static function (string $line): void {
        };
    }

    /** EuroAsia: oxirgi 1 kunda yaratilgan to'lanmagan polislar. */
    public function checkEai(): int
    {
        $activated = 0;
        $eai = new EuroAsiaService();

        foreach ($this->pendingPolices(Police::PROVIDER_EAI, '-1 days') as $police) {
            $activated += $this->guard($police, function (Police $police) use ($eai): bool {
                $dto = $eai->getPoliceByIdDTO($police->policeId);
                if (!$dto->success) {
                    $this->log("#{$police->id}: EAI javobi muvaffaqiyatsiz");
                    return false;
                }

                $isActive = $dto->status == 'ACTIVE';
                if ($isActive && !$police->claimActivation()) {
                    return false;
                }

                $police->status = $isActive ? 1 : 0;
                $police->pdfUrl = $dto->pdfUrl;
                $police->paymentId = $dto->paymentId;
                $police->payment_status = $dto->paymentStatus == 'COMPLETED' ? 1 : 0;
                $police->amount = $dto->amount / 100;
                $police->save();

                if ($police->payment_status) {
                    (new MarketingAttributionService())->recordPurchase($police);
                }

                if (!$police->status) {
                    $this->log("#{$police->id}: hali faol emas ({$dto->status})");
                    return false;
                }

                $this->deliver($police, "https://erp.eai.uz/portfolio/contracts/policy/{$police->policeId}/pdf");
                return true;
            });
        }

        return $activated;
    }

    /** Gross Insurance: oxirgi 5 kunda yaratilgan to'lanmagan polislar. */
    public function checkGross(): int
    {
        $activated = 0;

        foreach ($this->pendingPolices(Police::PROVIDER_GROSS, '-5 days') as $police) {
            $activated += $this->guard($police, function (Police $police): bool {
                $paid = $this->grossService()->checkPayment((string)$police->anketa_id);
                $this->log("#{$police->id}: anketa_id={$police->anketa_id} natija=" . ($paid ? 'SUCCESS' : 'false'));

                if (!$paid || !$police->claimActivation()) {
                    return false;
                }

                $police->status = 1;
                $police->pdfUrl = "https://ersp.e-osgo.uz/site/export-to-pdf?id={$police->policeId}";
                $police->payment_status = 1;
                $police->save(false);

                (new MarketingAttributionService())->recordPurchase($police);

                $this->deliver($police, $police->pdfUrl);
                return true;
            });
        }

        return $activated;
    }

    /**
     * Uzbekinvest: portfelda shartnoma tasdiqlanib, ersp.e-osgo.uz PDF havolasi
     * paydo bo'lsa — polis to'langan hisoblanadi. Admin "Uzbekinvest pauza"
     * qo'ygan bo'lsa (brauzerdan ishlayapti) tekshirilmaydi.
     */
    public function checkUzbekinvest(): int
    {
        if (Setting::isUzbekinvestPaused()) {
            $this->log('Uzbekinvest pauzada');
            return 0;
        }

        $polices = $this->pendingPolices(Police::PROVIDER_UZBEKINVEST, '-5 days');
        if (!$polices) {
            return 0;
        }

        // Alohida cookie/session papkasi: navbat worker'ining sessiyasiga tegmaydi.
        $service = UzbekInvestOsago::fromParams('check');
        $activated = 0;

        foreach ($polices as $police) {
            $activated += $this->guard($police, function (Police $police) use ($service): bool {
                $check = $service->checkPayment((string)$police->anketa_id);
                if (!$check['paid'] || !$police->claimActivation()) {
                    return false;
                }

                $police->status = 1;
                $police->payment_status = 1;
                $police->policeId = $check['uuid'];
                $police->pdfUrl = $check['pdf_url'];
                $police->save(false);

                (new MarketingAttributionService())->recordPurchase($police);

                $this->deliver($police, "https://ersp.e-osgo.uz/site/export-to-pdf?id={$police->policeId}");
                return true;
            });
        }

        return $activated;
    }

    // ── Yordamchilar ─────────────────────────────────────────────────────

    /** @return Police[] */
    private function pendingPolices(int $providerId, string $since): array
    {
        return Police::find()
            ->where(['status' => 0, 'provider_id' => $providerId])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime($since))])
            ->all();
    }

    /** Bitta polisni xatodan himoyalangan holda tekshiradi; faollashsa 1 qaytaradi. */
    private function guard(Police $police, callable $check): int
    {
        try {
            return $check($police) ? 1 : 0;
        } catch (\Throwable $e) {
            Yii::error("Polis #{$police->id} tekshiruvi xatosi: " . $e->getMessage(), 'police-check');
            $this->log("#{$police->id}: XATO — " . $e->getMessage());
            return 0;
        }
    }

    private function grossService(): GrossOsago
    {
        if ($this->grossService === null) {
            $dir = Yii::getAlias('@runtime/gross');
            FileHelper::createDirectory($dir);
            $this->grossService = GrossOsago::fromParams(0, $dir . '/gross_cookie_check.txt');
        }

        return $this->grossService;
    }

    /**
     * Faollashgan polis PDF'ini yuklab, foydalanuvchiga va buyurtmalar kanaliga
     * yuboradi, bonuslarni yozadi.
     */
    private function deliver(Police $police, string $pdfUrl): void
    {
        $user = Botuser::findOne(['id' => $police->user_id]);
        if (!$user) {
            $this->log("#{$police->id}: foydalanuvchi topilmadi");
            return;
        }

        $path = $this->download($pdfUrl, (string)$police->policeId);
        if ($path === null) {
            $this->sendMessage($user->chat_id, "Nimadir xato Operator bilan bog'laning");
            $this->log("#{$police->id}: PDF yuklab bo'lmadi ({$pdfUrl})");
            return;
        }

        $docText = $this->insuranceReadyText($user);
        $this->sendDocument($user->chat_id, $path, $docText);

        $this->addUserBonus($user, $police);
        $this->addReferralBonus($user, $police);

        $this->sendDocument(BotController::ORDER_CHANNEL, $path, $docText);
        $this->log("#{$police->id}: faollashtirildi, PDF yuborildi");
    }

    /**
     * PDF'ni backend/web/policeFiles/ ga (absolyut yo'l — konsoldan ishlaganda
     * joriy papkaga bog'liq emas) yuklaydi. Muvaffaqiyatsiz bo'lsa null.
     */
    private function download(string $url, string $id): ?string
    {
        $dir = Yii::getAlias('@backend/web/policeFiles');
        FileHelper::createDirectory($dir);
        $path = $dir . '/' . basename($id) . '.pdf';

        $fp = fopen($path, 'w+');
        if ($fp === false) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $ok = curl_exec($ch) !== false;
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!$ok) {
            $this->log('PDF yuklash xatosi: ' . curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);

        if (!$ok || $httpCode >= 400 || !filesize($path)) {
            return null;
        }

        return $path;
    }

    private function sendMessage($chatId, string $text): void
    {
        try {
            Yii::$app->telegram->sendMessage([
                'chat_id' => $chatId,
                'parse_mode' => 'html',
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'police-check');
        }
    }

    private function sendDocument($chatId, string $path, string $caption): void
    {
        try {
            Yii::$app->telegram->sendDocument([
                'chat_id' => $chatId,
                'parse_mode' => 'html',
                'caption' => $caption,
                'document' => new CURLFile($path),
            ]);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'police-check');
        }
    }

    private function userLang(Botuser $user): string
    {
        $data = $user->data ? json_decode($user->data, true) : [];
        return $data['lang'] ?? 'uz';
    }

    private function insuranceReadyText(Botuser $user): string
    {
        $lang = $this->userLang($user);
        $record = Text::findOne(['keyword' => 'insurance_ready']);
        if ($record && $record->$lang) {
            return $record->$lang;
        }
        return $lang === 'ru'
            ? "<b>✅ Ваша страховка готова!\n\n@smartsugurtabot</b>"
            : "<b>✅ Sug'urtangiz tayyor bo'ldi!\n\n@smartsugurtabot</b>";
    }

    private function addUserBonus(Botuser $user, Police $police): void
    {
        $plate = (string)($police->vehicles[0]->licenseNumber ?? '');
        $bonus = (new UserBonusCalculator())->calculate($police->amount, $plate)['amount'];
        $lang = $this->userLang($user);

        if (!Income::credit($user->id, $bonus, "Sug'urta bonusi (polis #{$police->id})", Income::TYPE_INSURANCE, $police->id)) {
            return;
        }

        $bonusFormatted = $this->formatMoney($bonus);

        $record = Text::findOne(['keyword' => 'user_bonus_message']);
        if ($record && $record->$lang) {
            $text = sprintf($record->$lang, $bonusFormatted);
        } elseif ($lang === 'ru') {
            $text = "🎉 Поздравляем!\nНа ваш счет зачислен бонус в размере <b>{$bonusFormatted}</b> сумов!\n\nНажмите <b>🏧 Кошелек</b> для вывода средств.";
        } else {
            $text = "🎉 Tabriklayman!\nSizning hisobingizga <b>{$bonusFormatted}</b> so'm bonus qo'shildi!\n\nChiqarib olish uchun <b>🏧 Hamyon</b> tugmasini bosing.";
        }

        $this->sendMessage($user->chat_id, $text);
    }

    private function addReferralBonus(Botuser $user, Police $police): void
    {
        if (!$user->referred_by) {
            return;
        }

        $referrer = Botuser::findOne($user->referred_by);
        if (!$referrer) {
            return;
        }

        $percent = Setting::getReferralPercent() ?: 0;
        $bonus = (int)($police->amount * $percent / 100);

        $credited = Income::credit(
            $referrer->id,
            $bonus,
            "Referal bonus — {$user->fname} {$user->lname} (polis #{$police->id})",
            Income::TYPE_REFERRAL,
            $police->id
        );
        if (!$credited) {
            return;
        }

        $refLang = $this->userLang($referrer);
        $bonusFormatted = $this->formatMoney($bonus);
        $userName = trim("{$user->fname} {$user->lname}") ?: "ID:{$user->id}";

        $record = Text::findOne(['keyword' => 'referral_bonus_message']);
        if ($record && $record->$refLang) {
            $text = sprintf($record->$refLang, $userName, $bonusFormatted);
        } elseif ($refLang === 'ru') {
            $text = "💰 Ваш реферал <b>{$userName}</b> оформил страховку!\nВам начислен бонус: <b>{$bonusFormatted}</b> сумов.\n\nНажмите <b>🏧 Кошелек</b> для вывода.";
        } else {
            $text = "💰 Referalingiz <b>{$userName}</b> sug'urta rasmiyllashtirdi!\nSizga bonus qo'shildi: <b>{$bonusFormatted}</b> so'm.\n\nChiqarib olish uchun <b>🏧 Hamyon</b> tugmasini bosing.";
        }

        $this->sendMessage($referrer->chat_id, $text);
    }

    private function formatMoney($number): string
    {
        return number_format($number, 0, '.', ' ');
    }

    private function log(string $line): void
    {
        ($this->log)($line);
    }
}
