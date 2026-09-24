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
            $phone = $ctx->data['message']['contact']['phone_number'];
            $ctx->phone = $phone;

            $police_data = [];
            $police_data['phone'] = substr($phone, -9);
            $ctx->police_data = $police_data;

            $ctx->sendMessageAdmin(json_encode($police_data));

            $this->proceedToVehicleStage($ctx);

        } elseif (preg_match('/^\+?\d{9,12}$/', $ctx->text)) {
            // The input is a valid number within the specified length.
            $phone = $ctx->text;

            $police_data = [];
            $police_data['phone'] = substr($phone, -9);
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

            $this->proceedToVehicleStage($ctx);
        } else {
            $ctx->sendMessage($ctx->getMText('phone ask again'));
        }
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
