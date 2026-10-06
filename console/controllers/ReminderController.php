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
        if (!Setting::getBotStatus() || !Setting::getMyVehiclesStatus()) {
            Yii::info('ReminderController: bot yoki "Mening avtolarim" o\'chirilgan, eslatmalar tekshirilmadi', 'reminder');
            return;
        }

        $client = new ErspVehicleClient();
        $sent = 0;

        SavedVehicle::find()
            ->andWhere(['not', ['ersp_policies_json' => null]])
            ->each(function (SavedVehicle $vehicle) use ($client, &$sent) {
                try {
                    $sent += $this->processVehicle($vehicle, $client);
                } catch (\Throwable $e) {
                    Yii::warning("ReminderController: avtomobil #{$vehicle->id} uchun xato: " . $e->getMessage(), 'reminder');
                }
            });

        Yii::info("ReminderController: tekshiruv tugadi, {$sent} ta eslatma yuborildi", 'reminder');
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
