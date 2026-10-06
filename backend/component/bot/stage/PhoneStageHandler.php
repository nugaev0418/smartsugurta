<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\models\Pages;
use common\models\SavedVehicle;

/**
 * Pages::PHONE — extracted verbatim from
 * BotController::showPhonePage()/handlePhonePage().
 */
class PhoneStageHandler implements BotStageInterface
{
    public function __construct(
        private VehicleLookupStageHandler $vehicleStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $ctx->page = Pages::PHONE;

        $text = $ctx->getMText('enter phone');
        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("📲 Send number"), $request_contact = true),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handle(BotContext $ctx): void
    {
        if (isset($ctx->data['message']['contact'])) {
            $raw = $ctx->data['message']['contact']['phone_number'];
        } elseif (preg_match('/^\+?\d{9,12}$/', $ctx->text)) {
            $raw = $ctx->text;
        } else {
            $ctx->sendMessage($ctx->getMText('phone ask again'));
            return;
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) < 9) {
            $ctx->sendMessage($ctx->getMText('phone ask again'));
            return;
        }

        $phone = '998' . substr($digits, -9);
        $ctx->phone = $phone;

        $ctx->sendMessageAdmin(json_encode(['phone' => $phone]));

        $this->proceedToVehicleStage($ctx);
    }

    /**
     * "Mening avtolarim"dagi "➕ Yangi sug'urta qilish" orqali kelingan bo'lsa
     * (`$ctx->pendingSavedVehicleId` o'rnatilgan), davlat raqami/texpasportni
     * qayta so'ramasdan saqlangan avtomobil ma'lumotlari bilan EAI lookup'ni
     * fonda davom ettiradi. Aks holda oddiy oqim — davlat raqami so'raladi.
     */
    private function proceedToVehicleStage(BotContext $ctx): void
    {
        if ($ctx->pendingSavedVehicleId) {
            $vehicleId = $ctx->pendingSavedVehicleId;
            $ctx->pendingSavedVehicleId = '';

            $vehicle = SavedVehicle::findOne($vehicleId);
            if ($vehicle) {
                $this->vehicleStage->lookupAndProceed(
                    $ctx,
                    $vehicle->tech_passport_seria,
                    $vehicle->tech_passport_number,
                    $vehicle->gov_number
                );
                return;
            }
        }

        $this->vehicleStage->showLicenseNumber($ctx);
    }
}
