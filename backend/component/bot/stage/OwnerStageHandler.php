<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\insurance\OwnerLookupService;
use backend\component\insurance\PassportTextParser;
use backend\models\Pages;

/**
 * Pages::OWNER_PASS — extracted verbatim from
 * BotController::showOwnerPassPage()/handleOwnerPassPage().
 */
class OwnerStageHandler implements BotStageInterface
{
    public function __construct(
        private DriverRestrictionStageHandler $driverRestrictionStage,
        private MainMenuStageHandler $mainMenu
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $ctx->page = Pages::OWNER_PASS;

        $text = $ctx->getMText("ask owner pass");

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ],
        ];
        $ctx->sendMessageWithKeyborad($text, $option);
    }

    public function handle(BotContext $ctx): void
    {
        $passportParser = new PassportTextParser();

        if (!is_null($ctx->text) && $passportParser->looksLikePassport($ctx->text)) {
            $parts = $passportParser->splitPassport($ctx->text);
            $seria = $parts['seria'];
            $number = $parts['number'];

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['owner']['passport'] = $seria.$number;
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

            $pinfl = $ctx->vehicleData['pinfl'];
            $dto = (new OwnerLookupService())->lookupByPinfl($seria, $number, $pinfl);
            $ctx->logApiCall('OwnerLookupService::lookupByPinfl', [
                'seria' => $seria,
                'number' => $number,
                'pinfl' => $pinfl,
            ], $dto);

            if (!$dto->success){
                $this->mainMenu->show($ctx, ['text' => $ctx->getMText('Owner found transport')]);
            }else{
                $ctx->ownerData = $dto;
                $this->driverRestrictionStage->showDriverRestriction($ctx);
            }

        }else{
            $this->show($ctx);
        }
    }
}
