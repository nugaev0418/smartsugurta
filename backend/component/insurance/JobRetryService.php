<?php

namespace backend\component\insurance;

use backend\controllers\BotController;
use backend\queue\JobRetryTimeoutJob;
use common\models\Botuser;
use common\models\JobRetry;
use common\models\Police;
use common\models\Setting;
use Yii;

/**
 * Polisa job'i (Gross/Uzbekinvest) barcha urinishdan so'ng muvaffaqiyatsiz bo'lganda:
 *  - admin online bo'lsa (Setting::getAdminOnline()) — adminga "qayta urinish" tugmalari
 *    yuboriladi, foydalanuvchiga "kuting" deyiladi; admin TIMEOUT_SECONDS ichida javob
 *    bermasa, foydalanuvchiga odatdagi uzilish xabari yuboriladi;
 *  - aks holda offerRetry() false qaytaradi va chaqiruvchi foydalanuvchiga darhol
 *    uzilish xabarini yuboradi (avvalgi xatti-harakat).
 *
 * Bir qaror faqat bir marta qabul qilinadi: holat atomik UPDATE (status 0 -> yangi) orqali
 * "egallanadi", shuning uchun tugma ikki marta bosilsa yoki taymaut bilan to'qnashsa,
 * ikkinchi urinish polisa dublikatini yaratmaydi.
 */
class JobRetryService
{
    public const TIMEOUT_SECONDS = 600;
    public const CALLBACK_PREFIX = 'jobretry:';

    public static function userFailureMessage(): string
    {
        return "Sug'urta kompaniyasi xizmatlarida uzilish bo'lmoqda, iltimos keyinroq qayta urinib ko'ring, noqulaylik uchun uzur so'raymiz!";
    }

    /**
     * @param array  $applicationData job'dagi (array)OsagoApplicationData
     * @param string $queueComponent  taymaut job'i qo'yiladigan navbat komponenti ('grossQueue' / 'uzbekInvestQueue')
     * @return bool true — admin ishga tortildi (foydalanuvchiga xabar yuborildi); false — chaqiruvchi o'zi xabar beradi
     */
    public function offerRetry(array $applicationData, int $chatId, int $failedProvider, string $reason, string $queueComponent): bool
    {
        if (!Setting::getAdminOnline() || empty($applicationData['plateNumber'])) {
            // Admin offline: tugmasiz, lekin shu bilan to'liq ma'lumotli xabar adminga baribir boradi.
            $this->sendToAdmin(
                $this->failureText(null, $applicationData, $chatId, $failedProvider, $reason)
                . "\n\nAdmin offline — foydalanuvchiga uzilish xabari yuborildi."
            );

            return false;
        }

        $record = new JobRetry([
            'chat_id' => $chatId,
            'application_data' => json_encode($applicationData, JSON_UNESCAPED_UNICODE),
            'failed_provider' => $failedProvider,
            'reason' => mb_substr($reason, 0, 500),
            'status' => JobRetry::STATUS_PENDING,
        ]);
        if (!$record->save(false)) {
            return false;
        }

        try {
            // Taymaut avval qo'yiladi: push muvaffaqiyatsiz bo'lsa foydalanuvchi abadiy kutib qolmasin.
            Yii::$app->$queueComponent->delay(self::TIMEOUT_SECONDS)->push(new JobRetryTimeoutJob(['retryId' => $record->id]));

            Yii::$app->telegram->sendMessage([
                'chat_id' => BotController::ADMIN_ID,
                'text' => $this->failureText($record->id, $applicationData, $chatId, $failedProvider, $reason),
                'parse_mode' => 'HTML',
                'reply_markup' => Yii::$app->telegram->buildInlineKeyBoard([
                    [
                        Yii::$app->telegram->buildInlineKeyboardButton('🔁 Gross', '', self::CALLBACK_PREFIX . $record->id . ':' . Police::PROVIDER_GROSS),
                        Yii::$app->telegram->buildInlineKeyboardButton('🔁 Uzbekinvest', '', self::CALLBACK_PREFIX . $record->id . ':' . Police::PROVIDER_UZBEKINVEST),
                    ],
                    [
                        Yii::$app->telegram->buildInlineKeyboardButton('❌ Bekor qilish (foydalanuvchiga xabar)', '', self::CALLBACK_PREFIX . $record->id . ':0'),
                    ],
                ]),
            ]);
        } catch (\Throwable $e) {
            Yii::error("JobRetry #{$record->id} taklifi yuborilmadi: " . $e->getMessage(), 'uzbekinvest');
            // Admin xabarni olmadi: foydalanuvchi kutmasin, chaqiruvchi darhol xabar beradi.
            JobRetry::updateAll(
                ['status' => JobRetry::STATUS_CANCELLED, 'resolved_at' => date('Y-m-d H:i:s')],
                ['id' => $record->id, 'status' => JobRetry::STATUS_PENDING]
            );

            return false;
        }

        $this->notifyUser($chatId, "Arizangiz qayta tekshirilmoqda, iltimos biroz kuting.\nВаша заявка перепроверяется, пожалуйста, подождите.");

        return true;
    }

    /**
     * Admin tugmasi bosilganda chaqiriladi. $choice: 0 — bekor qilish, aks holda Police::PROVIDER_*.
     * Adminga ko'rsatiladigan natija matnini qaytaradi.
     */
    public function decide(int $retryId, int $choice): string
    {
        if ($choice !== 0 && !in_array($choice, [Police::PROVIDER_GROSS, Police::PROVIDER_UZBEKINVEST], true)) {
            return "Noma'lum provayder.";
        }

        $record = JobRetry::findOne($retryId);
        if (!$record) {
            return "Yozuv topilmadi (#{$retryId}).";
        }

        $claimed = JobRetry::updateAll([
            'status' => $choice === 0 ? JobRetry::STATUS_CANCELLED : JobRetry::STATUS_RETRIED,
            'retried_provider' => $choice ?: null,
            'resolved_at' => date('Y-m-d H:i:s'),
        ], ['id' => $retryId, 'status' => JobRetry::STATUS_PENDING]);

        if ($claimed === 0) {
            $current = (int)JobRetry::find()->select('status')->where(['id' => $retryId])->scalar();

            return "#{$retryId} allaqachon ishlangan (" . $this->statusName($current) . ').';
        }

        if ($choice === 0) {
            $this->notifyUser((int)$record->chat_id, self::userFailureMessage());

            return "❌ #{$retryId} bekor qilindi, foydalanuvchiga xabar yuborildi.";
        }

        try {
            $botuser = Botuser::find()->where(['chat_id' => $record->chat_id])->one();
            if (!$botuser) {
                throw new \RuntimeException("Botuser topilmadi (chat_id: {$record->chat_id})");
            }

            $data = OsagoApplicationData::fromArray(json_decode($record->application_data, true) ?: []);
            $jobId = Yii::createObject(OsagoSubmissionService::class)->queueForProvider($data, $botuser, $choice);
        } catch (\Throwable $e) {
            Yii::error("JobRetry #{$retryId} qayta yuborilmadi: " . $e->getMessage(), 'uzbekinvest');
            JobRetry::updateAll(['status' => JobRetry::STATUS_FAILED], ['id' => $retryId]);
            $this->notifyUser((int)$record->chat_id, self::userFailureMessage());

            return "⚠️ #{$retryId} qayta yuborilmadi: " . $e->getMessage() . "\nFoydalanuvchiga uzilish xabari yuborildi.";
        }

        return "🔁 #{$retryId} " . (Police::getProviderList()[$choice] ?? $choice) . ' navbatiga qayta yuborildi (job ' . json_encode($jobId) . ').';
    }

    /**
     * JobRetryTimeoutJob: admin TIMEOUT_SECONDS ichida javob bermagan bo'lsa.
     */
    public function expire(int $retryId): void
    {
        $record = JobRetry::findOne($retryId);
        if (!$record) {
            return;
        }

        $claimed = JobRetry::updateAll(
            ['status' => JobRetry::STATUS_EXPIRED, 'resolved_at' => date('Y-m-d H:i:s')],
            ['id' => $retryId, 'status' => JobRetry::STATUS_PENDING]
        );
        if ($claimed === 0) {
            return;
        }

        $this->notifyUser((int)$record->chat_id, self::userFailureMessage());
        try {
            Yii::$app->telegram->sendMessage([
                'chat_id' => BotController::ADMIN_ID,
                'text' => "⏱ #{$retryId}: vaqt tugadi, foydalanuvchiga uzilish xabari yuborildi.",
            ]);
        } catch (\Throwable $e) {
            Yii::warning("JobRetry #{$retryId} taymaut xabari adminga yuborilmadi: " . $e->getMessage(), 'uzbekinvest');
        }
    }

    private const PERIOD_NAMES = [7 => '1 yil', 1 => '6 oy', 8 => '20 kun'];

    /**
     * "Polis yaratilmadi" xabari: polis uchun kiritilgan BARCHA ma'lumotlar bilan (admin qo'lda
     * yaratishi yoki muammoni tekshirishi mumkin bo'lishi uchun). $retryId berilsa tugmalar bilan
     * birga ishlatiladi.
     */
    private function failureText(?int $retryId, array $d, int $chatId, int $failedProvider, string $reason): string
    {
        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML401);
        $date = fn($iso) => $iso ? $h(substr((string)$iso, 0, 10)) : '—';
        $provider = Police::getProviderList()[$failedProvider] ?? $failedProvider;

        $user = Botuser::find()->where(['chat_id' => $chatId])->one();
        $userLine = $user
            ? trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) . ($user->username ? ' @' . $user->username : '') . ($user->phone ? ', ' . $user->phone : '')
            : '';

        $lines = [];
        $lines[] = '🆘 <b>Polis yaratilmadi</b>' . ($retryId ? " (#{$retryId})" : '');
        $lines[] = 'Provayder: <b>' . $h($provider) . '</b>';
        $lines[] = 'Xato: ' . $h($reason !== '' ? $reason : "noma'lum");
        $lines[] = '';
        $lines[] = '👤 Foydalanuvchi: ' . $h($userLine !== '' ? $userLine . ' ' : '') . '<code>' . $h($chatId) . '</code>';
        $lines[] = '📞 Telefon: ' . $h($d['eaiPhoneNumber'] ?? '—');
        $lines[] = '🚗 Avto: <b>' . $h($d['plateNumber'] ?? '—') . '</b>, texpassport ' . $h(($d['techSeria'] ?? '') . ($d['techNumber'] ?? ''));

        if (!empty($d['isOrganization'])) {
            $lines[] = '🏢 Egasi (tashkilot): INN ' . $h($d['organizationInn'] ?? '—') . ($d['organizationName'] ?? null ? ', ' . $h($d['organizationName']) : '');
        } else {
            $fio = trim(($d['ownerLastName'] ?? '') . ' ' . ($d['ownerFirstName'] ?? '') . ' ' . ($d['ownerMiddleName'] ?? ''));
            $lines[] = '🧍 Egasi: ' . $h($fio !== '' ? $fio . ', ' : '')
                . 'passport ' . $h(($d['ownerSeria'] ?? '') . ($d['ownerNumber'] ?? ''))
                . ', tug\'ilgan ' . $date($d['ownerBirthDateIso'] ?? null)
                . (!empty($d['ownerPinfl']) ? ', PINFL ' . $h($d['ownerPinfl']) : '');
        }

        $lines[] = '🛡 Polis: ' . (!empty($d['driverRestriction']) ? 'cheklangan' : 'cheklanmagan')
            . ', muddat ' . $h(self::PERIOD_NAMES[$d['periodType'] ?? 0] ?? ('kod ' . ($d['periodType'] ?? '?')))
            . ', boshlanishi ' . $date($d['startAtIso'] ?? null)
            . ', to\'lov ' . $h($d['gateway'] ?? '—');

        $drivers = $d['drivers'] ?? [];
        if ($drivers) {
            $lines[] = '👥 Haydovchilar:';
            foreach ($drivers as $i => $driver) {
                $name = trim(($driver['lastName'] ?? '') . ' ' . ($driver['firstName'] ?? ''));
                $lines[] = ($i + 1) . '. ' . $h(($driver['seria'] ?? '') . ($driver['number'] ?? ''))
                    . ', tug\'ilgan ' . $date($driver['birthDateIso'] ?? null)
                    . ($name !== '' ? ', ' . $h($name) : '')
                    . (!empty($driver['relativeType']) ? ', qarindoshlik kodi ' . $h($driver['relativeType']) : '');
            }
        }

        if ($retryId) {
            $lines[] = '';
            $lines[] = 'Qayta urinish uchun provayderni tanlang. ' . (int)(self::TIMEOUT_SECONDS / 60) . " daqiqada javob bo'lmasa, foydalanuvchiga uzilish xabari yuboriladi.";
        }

        return implode("\n", $lines);
    }

    private function sendToAdmin(string $text): void
    {
        try {
            Yii::$app->telegram->sendMessage(['chat_id' => BotController::ADMIN_ID, 'text' => $text, 'parse_mode' => 'HTML']);
        } catch (\Throwable $e) {
            Yii::error('Polis yaratilmadi xabari adminga yuborilmadi: ' . $e->getMessage(), 'uzbekinvest');
        }
    }

    private function statusName(int $status): string
    {
        return match ($status) {
            JobRetry::STATUS_RETRIED => 'qayta yuborilgan',
            JobRetry::STATUS_EXPIRED => 'vaqti tugagan',
            JobRetry::STATUS_CANCELLED => 'bekor qilingan',
            JobRetry::STATUS_FAILED => 'qayta yuborishda xato',
            default => 'kutilmoqda',
        };
    }

    private function notifyUser(int $chatId, string $text): void
    {
        try {
            Yii::$app->telegram->sendMessage(['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']);
        } catch (\Throwable $e) {
            Yii::warning("JobRetry: foydalanuvchiga ({$chatId}) xabar yuborilmadi: " . $e->getMessage(), 'uzbekinvest');
        }
    }
}
