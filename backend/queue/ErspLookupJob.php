<?php

namespace backend\queue;

use backend\component\bot\BotTextService;
use backend\ersp\ErspVehicleClient;
use common\models\Botuser;
use common\models\SavedVehicle;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * "Mening avtolarim" bo'limidagi "✅ Tekshirish" tugmasi bosilganda ishga
 * tushadi — ersp.e-osgo.uz'dan (captcha+AI, sekin) avtomobilning amaldagi
 * polisalari ro'yxatini olib, natijani `saved_vehicle` jadvaliga yozadi va
 * (chatId berilgan bo'lsa) Telegram orqali xabar qiladi. GrossOsagoJob bilan
 * bir xil naqsh.
 */
class ErspLookupJob extends BaseObject implements JobInterface
{
    public int $savedVehicleId;
    public ?string $chatId = null;

    public function execute($queue): void
    {
        $vehicle = SavedVehicle::findOne($this->savedVehicleId);
        if (!$vehicle) {
            Yii::error("ErspLookupJob: SavedVehicle topilmadi (id: {$this->savedVehicleId})", 'ersp');
            return;
        }

        try {
            $client = new ErspVehicleClient();
            $html   = $client->lookupVehiclePolicies(
                $vehicle->tech_passport_seria,
                $vehicle->tech_passport_number,
                $vehicle->gov_number,
                Yii::$app->params['openai']['apiKey']
            );

            $policies = $client->extractPolicies($html);

            $vehicle->ersp_check_status  = SavedVehicle::ERSP_STATUS_DONE;
            $vehicle->ersp_policies_json = json_encode($policies, JSON_UNESCAPED_UNICODE);
            $vehicle->ersp_checked_at    = date('Y-m-d H:i:s');
            $vehicle->save(false);

            Yii::info("ErspLookupJob: muvaffaqiyatli (savedVehicleId: {$this->savedVehicleId}, polisalar: " . count($policies) . ")", 'ersp');

            $this->notify($vehicle, $client->filterActivePolicies($policies), $client);
        } catch (\Throwable $e) {
            $vehicle->ersp_check_status = SavedVehicle::ERSP_STATUS_FAILED;
            $vehicle->save(false);

            Yii::error("ErspLookupJob xato (savedVehicleId: {$this->savedVehicleId}): " . $e->getMessage(), 'ersp');

            if ($this->chatId) {
                $lang = $this->getUserLang($this->chatId);
                $this->sendMessage(
                    $this->chatId,
                    sprintf($this->m($lang, 'my vehicles check failed'), $vehicle->gov_number)
                );
            }
        }
    }

    private function notify(SavedVehicle $vehicle, array $activePolicies, ErspVehicleClient $client): void
    {
        if (!$this->chatId) {
            return;
        }

        $lang = $this->getUserLang($this->chatId);

        if (!$activePolicies) {
            $this->sendMessage(
                $this->chatId,
                sprintf($this->m($lang, 'my vehicles no active policies notify'), $vehicle->gov_number)
            );
            return;
        }

        $unknownPeriod = $this->m($lang, 'my vehicles unknown period');
        $lines = [sprintf($this->m($lang, 'my vehicles active policies notify title'), $vehicle->gov_number)];
        foreach ($activePolicies as $policy) {
            $lines[] = '• ' . ($client->remainingLabel($policy) ?? $unknownPeriod);
        }

        $this->sendMessage($this->chatId, implode("\n", $lines));
    }

    private function getUserLang(string $chatId): string
    {
        $botuser = Botuser::find()->select(['data'])->where(['chat_id' => $chatId])->one();
        if ($botuser && $botuser->data) {
            $data = json_decode($botuser->data, true);
            if (($data['lang'] ?? null) === 'ru') {
                return 'ru';
            }
        }

        return 'uz';
    }

    private function m(string $lang, string $keyword): string
    {
        return (new BotTextService())->m($lang, $keyword);
    }

    private function sendMessage(string $chatId, string $text): void
    {
        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }
}
