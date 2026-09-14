<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\insurance\VehicleLookupService;
use backend\models\Pages;

/**
 * Pages::LISENCE_NUMBER, TEXPASS_SERIA, TEXPASS_NUMBER — extracted verbatim
 * from BotController::showLisenceNumberPage()/handleLisenceNumberPage()/
 * showTexPassSeriaPage()/handleTexPassPage()/showTexPassNumberPage()/
 * handleTexPassNumberPage().
 */
class VehicleLookupStageHandler implements BotStageInterface
{
    public function __construct(
        private OwnerStageHandler $ownerStage,
        private DriverRestrictionStageHandler $driverRestrictionStage,
        private MainMenuStageHandler $mainMenu
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::LISENCE_NUMBER;
        switch ($page) {
            case Pages::TEXPASS_SERIA:
                $this->showTexPassSeria($ctx);
                break;
            case Pages::TEXPASS_NUMBER:
                $this->showTexPassNumber($ctx);
                break;
            default:
                $this->showLicenseNumber($ctx);
        }
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::TEXPASS_SERIA:
                $this->handleTexPass($ctx);
                break;
            case Pages::TEXPASS_NUMBER:
                $this->handleTexPassNumber($ctx);
                break;
            case Pages::LISENCE_NUMBER:
                $this->handleLicenseNumber($ctx);
                break;
        }
    }

    public function showLicenseNumber(BotContext $ctx): void
    {
        $ctx->page = Pages::LISENCE_NUMBER;

        $text = $ctx->getMText("ask lisence number");

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ],
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleLicenseNumber(BotContext $ctx): void
    {
        if (!is_null($ctx->text)) {
            $ctx->lisenceNumber = str_replace(" ", '', strtoupper($ctx->text));
            $this->showTexPassSeria($ctx);

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['vehicle']['gov_number'] = $ctx->lisenceNumber;
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

        }else{
            $this->showLicenseNumber($ctx);
        }
    }

    public function showTexPassSeria(BotContext $ctx): void
    {
        $ctx->page = Pages::TEXPASS_SERIA;

        $text = $ctx->getMText("ask texpasseria");

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ],
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleTexPass(BotContext $ctx): void
    {
        if (empty($ctx->text)) {
            $this->showTexPassSeria($ctx);
            return;
        }

        $value = strtoupper(trim($ctx->text));
        $value = str_replace(' ', '', $value);

        if (!preg_match('/^([A-Z]{3})(\d{7})$/', $value, $matches)) {
            $this->showTexPassSeria($ctx);
            return;
        }

        $ctx->texPassSeria  = $matches[1];
        $ctx->texPassNumber = $matches[2];

        $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
        $police_data['vehicle']['seria'] = $ctx->texPassSeria;
        $police_data['vehicle']['number'] = $ctx->texPassNumber;
        $ctx->police_data = $police_data;
        $ctx->sendMessageAdmin(json_encode($police_data));

        $dto = (new VehicleLookupService())->lookup(
            $ctx->texPassSeria,
            $ctx->texPassNumber,
            $ctx->lisenceNumber
        );
        $ctx->logApiCall('VehicleLookupService::lookup', [
            'techPassportSeria' => $ctx->texPassSeria,
            'techPassportNumber' => $ctx->texPassNumber,
            'licenseNumber' => $ctx->lisenceNumber,
        ], $dto);

        if (!$dto->success) {
            $this->mainMenu->show($ctx, ['text' => $ctx->getMText('Not found transport')]);
            return;
        }

        $ctx->vehicleData = $dto;

        if ($dto->ownerType === 'PERSON') {

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['owner']['is_org'] = false;
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

            $this->ownerStage->show($ctx);
        } else {

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['owner']['is_org'] = true;
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

            $this->driverRestrictionStage->showDriverRestriction($ctx);
        }
    }

    public function showTexPassNumber(BotContext $ctx): void
    {
        $ctx->page = Pages::TEXPASS_NUMBER;

        $text = $ctx->getMText("ask texpasnumber");

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ],
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handleTexPassNumber(BotContext $ctx): void
    {
        if (!is_null($ctx->text) && is_numeric($ctx->text) && strlen($ctx->text) == 7) {
            $ctx->texPassNumber = $ctx->text;

            $dto = (new VehicleLookupService())->lookup(
                $ctx->texPassSeria,
                $ctx->texPassNumber,
                $ctx->lisenceNumber
            );
            $ctx->logApiCall('VehicleLookupService::lookup', [
                'techPassportSeria' => $ctx->texPassSeria,
                'techPassportNumber' => $ctx->texPassNumber,
                'licenseNumber' => $ctx->lisenceNumber,
            ], $dto);

            if (!$dto->success) {
                // xato ishlovi
                $this->mainMenu->show($ctx, ['text' => $ctx->getMText('Not found transport')]);
            }else{
                $ctx->vehicleData = $dto;
                if ($dto->ownerType == 'PERSON'){
                    $this->ownerStage->show($ctx);
                }else{
                    $this->driverRestrictionStage->showDriverRestriction($ctx);
                }
            }
        }else{
            $this->showTexPassNumber($ctx);
        }
    }
}
