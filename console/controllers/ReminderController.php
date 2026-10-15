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

        SavedVehicle::find()
            ->andWhere(['not', ['ersp_policies_json' => null]])
            ->each(function (SavedVehicle $vehicle) use ($client, &$checked, &$sent) {
                $checked++;
                try {
                    $sent += $this->processVehicle($vehicle, $client);
                } catch (\Throwable $e) {
                    Yii::warning("ReminderController: avtomobil #{$vehicle->id} uchun xato: " . $e->getMessage(), 'reminder');
                    $this->stdout("  Avtomobil #{$vehicle->id}: xato — " . $e->getMessage() . "\n", Console::FG_RED);
                }
            });

        $summary = "Tekshirildi: {$checked} ta avtomobil, yuborildi: {$sent} ta eslatma";
        Yii::info("ReminderController: tekshiruv tugadi, {$sent} ta eslatma yuborildi", 'reminder');
        $this->stdout($summary . "\n", $sent > 0 ? Console::FG_GREEN : Console::FG_YELLOW);
    }

    private function processVehicle(SavedVehicle $vehicle, ErspVehicleClient $client): int
    {
        $activePolicies = $client->filterActivePolicies($vehicle->getCachedPolicies());
        $sent = 0;

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

    private function sendReminder(SavedVehicle $vehicle, int $days): bool
    {
        $botuser = $vehicle->botuser;
        if (!$botuser || !$botuser->chat_id) {
            return false;
        }

        $data = $botuser->data ? json_decode($botuser->data, true) : [];
        $lang = ($data['lang'] ?? null) === 'ru' ? 'ru' : 'uz';

        $record = Text::findOne(['keyword' => 'osago_expiry_reminder']);
        if (!$record || !$record->$lang) {
            return false;
        }

        $text = sprintf($record->$lang, $vehicle->gov_number, $days);

        Yii::$app->telegram->sendMessage([
            'chat_id' => $botuser->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        Yii::$app->telegram->sendMessage([
            'chat_id' => BotController::ADMIN_ID,
            'text' => "🔔 Eslatma yuborildi (chat_id: {$botuser->chat_id}):\n\n{$text}",
            'parse_mode' => 'HTML',
        ]);

        return true;
    }
}
