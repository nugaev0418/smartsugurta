<?php

namespace console\controllers;

use backend\controllers\BotController;
use backend\ersp\ErspVehicleClient;
use common\models\OsagoExpiryReminder;
use common\models\SavedVehicle;
use common\models\Setting;
use common\models\Text;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * "Mening avtolarim"dagi saqlangan avtomobillarning (keshlangan) OSAGO
 * polisasi muddati tugashiga 7/3/1 kun qolganda foydalanuvchiga eslatma
 * yuboradi. Kuniga bir marta `deploy/systemd/osago-reminder.timer` orqali
 * ishga tushiriladi — qo'lda sinash uchun: `php yii reminder/expiry-check`.
 */
class ReminderController extends Controller
{
    private const MILESTONES = [7, 3, 1];

    /** `osago_expiry_reminder.milestone_days` da "sug'urta yo'q" eslatmasi belgisi (7/3/1 bilan to'qnashmaydi). */
    private const NO_POLICY_MILESTONE = 0;
    private const NO_POLICY_REPEAT_DAYS = 7;

    public function actionExpiryCheck(): void
    {
        $this->stdout('[' . date('Y-m-d H:i:s') . "] Muddat tugashi eslatmasini tekshirish boshlandi...\n");

        if (!Setting::getBotStatus() || !Setting::getMyVehiclesStatus()) {
            $message = 'Bot yoki "Mening avtolarim" o\'chirilgan, eslatmalar tekshirilmadi';
            Yii::info("ReminderController: {$message}", 'reminder');
            $this->stdout($message . "\n", Console::FG_YELLOW);
            return;
        }

        $client = new ErspVehicleClient();
        $checked = 0;
        $sent = 0;

        // DIQQAT: ActiveQuery::each() callback qabul qilmaydi — faqat
        // foreach'da iteratsiya qilinganda haqiqatan bazaga so'rov yuboradi
        // (lazy BatchQueryResult qaytaradi). Avval ->each($closure) deb
        // chaqirilgani uchun $closure shunchaki $batchSize sifatida
        // yutilib, natija hech qayerda iteratsiya qilinmasdan tashlab
        // yuborilardi — so'rov UMUMAN bajarilmay, har doim 0/0 chiqardi.
        foreach (SavedVehicle::find()->andWhere(['not', ['ersp_policies_json' => null]])->each() as $vehicle) {
            $checked++;
            try {
                $sent += $this->processVehicle($vehicle, $client);
            } catch (\Throwable $e) {
                Yii::warning("ReminderController: avtomobil #{$vehicle->id} uchun xato: " . $e->getMessage(), 'reminder');
                $this->stdout("  Avtomobil #{$vehicle->id}: xato — " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $summary = "Tekshirildi: {$checked} ta avtomobil, yuborildi: {$sent} ta eslatma";
        Yii::info("ReminderController: tekshiruv tugadi, {$sent} ta eslatma yuborildi", 'reminder');
        $this->stdout($summary . "\n", $sent > 0 ? Console::FG_GREEN : Console::FG_YELLOW);
    }

    /**
     * Sug'urtasi yo'q (yoki hali tekshirilmagan) avtomobillarni har kuni bir
     * marta jim (chatId=null) ERSP tekshiruviga yuboradi. Kuniga bir marta
     * `deploy/systemd/osago-check.timer` orqali ishga tushiriladi.
     */
    public function actionCheckUninsured(): void
    {
        $this->stdout('[' . date('Y-m-d H:i:s') . "] Sug'urtasiz avtomobillarni tekshiruvga yuborish boshlandi...\n");

        if (!Setting::getBotStatus() || !Setting::getMyVehiclesStatus()) {
            $this->stdout("Bot yoki \"Mening avtolarim\" o'chirilgan, o'tkazib yuborildi\n", Console::FG_YELLOW);
            return;
        }

        $client = new ErspVehicleClient();
        $queued = 0;

        // Eslatma: ->each() faqat foreach'da ishlaydi (yuqoridagi izohga qarang).
        foreach (SavedVehicle::find()->each() as $vehicle) {
            try {
                // Worker yiqilib 'checking'da qotib qolgan yozuvlarni 1 soatdan keyin bo'shatish.
                if ($vehicle->ersp_check_status === SavedVehicle::ERSP_STATUS_CHECKING
                    && (!$vehicle->ersp_checked_at || time() - strtotime($vehicle->ersp_checked_at) > 3600)) {
                    $vehicle->ersp_check_status = SavedVehicle::ERSP_STATUS_FAILED;
                    $vehicle->save(false);
                }

                if ($client->filterActivePolicies($vehicle->getCachedPolicies())) {
                    continue; // amaldagi polisasi bor
                }

                // Har bir job orasida 15 soniya — ersp.e-osgo.uz'ni bosmaslik uchun.
                if ($vehicle->enqueueCheck(null, $queued * 15)) {
                    $queued++;
                }
            } catch (\Throwable $e) {
                Yii::warning("ReminderController: avtomobil #{$vehicle->id} tekshiruvga yuborilmadi: " . $e->getMessage(), 'reminder');
                $this->stdout("  Avtomobil #{$vehicle->id}: xato — " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        Yii::info("ReminderController: {$queued} ta sug'urtasiz avtomobil tekshiruvga yuborildi", 'reminder');
        $this->stdout("Tekshiruvga yuborildi: {$queued} ta avtomobil\n", $queued > 0 ? Console::FG_GREEN : Console::FG_YELLOW);
    }

    private function processVehicle(SavedVehicle $vehicle, ErspVehicleClient $client): int
    {
        $activePolicies = $client->filterActivePolicies($vehicle->getCachedPolicies());
        $sent = 0;

        if (!$activePolicies) {
            return $this->processUninsured($vehicle);
        }

        foreach ($activePolicies as $policy) {
            $days = $client->daysRemaining($policy);
            if (!in_array($days, self::MILESTONES, true)) {
                continue;
            }

            $endDate = $client->endDateLabel($policy);
            if ($endDate === null) {
                continue;
            }

            $alreadySent = OsagoExpiryReminder::find()->where([
                'saved_vehicle_id' => $vehicle->id,
                'policy_end_date' => $endDate,
                'milestone_days' => $days,
            ])->exists();

            if ($alreadySent) {
                continue;
            }

            if ($this->sendReminder($vehicle, $days)) {
                (new OsagoExpiryReminder([
                    'saved_vehicle_id' => $vehicle->id,
                    'policy_end_date' => $endDate,
                    'milestone_days' => $days,
                ]))->save(false);
                $sent++;
                $this->stdout("  Avtomobil #{$vehicle->id} ({$vehicle->gov_number}): {$days} kun qoldi — eslatma yuborildi\n", Console::FG_GREEN);
            }
        }

        return $sent;
    }

    /**
     * Amaldagi polisasi yo'q avtomobil uchun "sug'urta yo'q" eslatmasi — faqat
     * yangi (oxirgi ~36 soatdagi) muvaffaqiyatli tekshiruvga asoslanadi va bir
     * avtomobilga NO_POLICY_REPEAT_DAYS kunda bir martadan ko'p yuborilmaydi.
     */
    private function processUninsured(SavedVehicle $vehicle): int
    {
        if ($vehicle->ersp_check_status !== SavedVehicle::ERSP_STATUS_DONE
            || !$vehicle->ersp_checked_at
            || time() - strtotime($vehicle->ersp_checked_at) > 36 * 3600) {
            return 0;
        }

        $recentlySent = OsagoExpiryReminder::find()
            ->where(['saved_vehicle_id' => $vehicle->id, 'milestone_days' => self::NO_POLICY_MILESTONE])
            ->andWhere(['>=', 'sent_at', date('Y-m-d H:i:s', strtotime('-' . self::NO_POLICY_REPEAT_DAYS . ' days'))])
            ->exists();
        if ($recentlySent) {
            return 0;
        }

        if (!$this->sendReminder($vehicle, null, 'osago_no_policy_reminder')) {
            return 0;
        }

        (new OsagoExpiryReminder([
            'saved_vehicle_id' => $vehicle->id,
            'policy_end_date' => date('Y-m-d'),
            'milestone_days' => self::NO_POLICY_MILESTONE,
        ]))->save(false);
        $this->stdout("  Avtomobil #{$vehicle->id} ({$vehicle->gov_number}): sug'urta yo'q — eslatma yuborildi\n", Console::FG_GREEN);

        return 1;
    }

    private function sendReminder(SavedVehicle $vehicle, ?int $days, string $keyword = 'osago_expiry_reminder'): bool
    {
        $botuser = $vehicle->botuser;
        if (!$botuser || !$botuser->chat_id) {
            return false;
        }

        $data = $botuser->data ? json_decode($botuser->data, true) : [];
        $lang = ($data['lang'] ?? null) === 'ru' ? 'ru' : 'uz';

        $record = Text::findOne(['keyword' => $keyword]);
        if (!$record || !$record->$lang) {
            return false;
        }

        $text = $days === null
            ? sprintf($record->$lang, $vehicle->gov_number)
            : sprintf($record->$lang, $vehicle->gov_number, $days);

        Yii::$app->telegram->sendMessage([
            'chat_id' => $botuser->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        \backend\component\LogChannel::send("🔔 Eslatma yuborildi (chat_id: {$botuser->chat_id}):\n\n{$text}", 'HTML');

        return true;
    }
}
