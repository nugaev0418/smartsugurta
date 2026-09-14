<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\models\Pages;
use DateTime;

/**
 * Pages::DRIVER_RESTRICTION_TYPE, OWNER_IS_DRIVER — extracted verbatim from
 * BotController::showDriverRestrictionPage()/handleDriverRestrictionPage()/
 * showOwnerIsDriverPage()/handleOwnerIsDriverPage().
 */
class DriverRestrictionStageHandler implements BotStageInterface
{
    public function __construct(
        private PoliceSeasonStageHandler $policeSeasonStage,
        private DriverStageHandler $driverStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::DRIVER_RESTRICTION_TYPE;
        if ($page === Pages::OWNER_IS_DRIVER) {
            $this->showOwnerIsDriver($ctx);
            return;
        }

        $this->showDriverRestriction($ctx);
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::OWNER_IS_DRIVER:
                $this->handleOwnerIsDriver($ctx);
                break;
            case Pages::DRIVER_RESTRICTION_TYPE:
                $this->handleDriverRestriction($ctx);
                break;
        }
    }

    public function showDriverRestriction(BotContext $ctx): void
    {
        $ctx->page = Pages::DRIVER_RESTRICTION_TYPE;

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText('Limited')),
                $ctx->telegram->buildKeyboardButton($ctx->getMText('Not limited')),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($ctx->getMText('Choose driver restriction type'), $option);
    }

    public function handleDriverRestriction(BotContext $ctx): void
    {
        if (in_array($ctx->getKeywordText($ctx->text), ['Limited', 'Not limited'])){
            $ctx->driverRestriction = $ctx->getKeywordText($ctx->text);
            switch ($ctx->getKeywordText($ctx->text)) {
                case "Limited":
                    $ctx->drivers = '';
                    $ctx->sendMessage($ctx->getMText("owner required message"));
                    $this->showOwnerIsDriver($ctx);
                    $type_limit = 'limited';
                    break;
                case "Not limited":
                    $this->policeSeasonStage->showPoliceSeason($ctx);
                    $type_limit = 'unlimited';
                    break;
            }

            $police_data = $ctx->police_data != '' ? $ctx->police_data : [];
            $police_data['policy_type'] = $type_limit;
            $ctx->police_data = $police_data;
            $ctx->sendMessageAdmin(json_encode($police_data));

        }else{
            $this->showDriverRestriction($ctx);
        }
    }

    public function showOwnerIsDriver(BotContext $ctx): void
    {
        $ctx->page = Pages::OWNER_IS_DRIVER;

        $option = [
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText('Yes')),
                $ctx->telegram->buildKeyboardButton($ctx->getMText('No')),
            ],
            [
                $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
            ]
        ];
        $ctx->sendMessageWithKeyborad($ctx->getMText('If the car owner also needs to drive, he should be added as a driver! Should the car owner be added too?'), $option);
    }

    public function handleOwnerIsDriver(BotContext $ctx): void
    {
        switch ($ctx->getKeywordText($ctx->text)) {
            case "Yes":

                $date = $ctx->ownerData['birthDate'];

                $dt = new DateTime($date);
                $birthdate = $dt->format('d.m.Y');

                $owner_data = $ctx->ownerData['seria'] . $ctx->ownerData['number'] . ' ' . $birthdate;

                $ctx->sendMessageAdmin(json_encode($owner_data));

                $this->driverStage->handleDriver($ctx, $owner_data);

                return;
            case "No":
                $this->driverStage->showDriver($ctx);
                return;
            default:
                $this->showOwnerIsDriver($ctx);
                return;
        }
    }
}
