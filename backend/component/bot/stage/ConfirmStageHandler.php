<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\EuroAsiaService;
use backend\component\insurance\OsagoApplicationData;
use backend\component\insurance\OsagoRequestBuilder;
use backend\component\insurance\OsagoSubmissionService;
use backend\models\EuroAsia;
use backend\models\Pages;
use common\models\Botuser;
use Yii;

/**
 * Pages::CONFIRM_PAGE — show() extracted verbatim from
 * BotController::showConfirmPage(). handle() used to contain the whole
 * EAI/Gross payload-building and submission logic inline (~230 lines,
 * including a dead `if (true) {...} else {...}` branch that never ran) —
 * that's now OsagoRequestBuilder + OsagoSubmissionService, shared with
 * WebAppController::actionSubmit().
 */
class ConfirmStageHandler implements BotStageInterface
{
    public function __construct(
        private MainMenuStageHandler $mainMenu,
        private OsagoRequestBuilder $requestBuilder,
        private OsagoSubmissionService $submissionService
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        try {
            $ctx->page = Pages::CONFIRM_PAGE;

            $option = [
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Continue ✅")),
                ],
                [
                    $ctx->telegram->buildKeyboardButton($ctx->getMText("Cancel")),
                ]
            ];

            $vehicleData = $ctx->vehicleData;
            $autoNumber = $ctx->lisenceNumber;
            $texPassSeria = $ctx->texPassSeria;
            $texPassNumber = $ctx->texPassNumber;
            $texPass = $texPassSeria . $texPassNumber;

            if ($vehicleData['ownerType'] == 'ORGANIZATION'){
                $arizachi = $vehicleData['name'];
            }

            switch ($vehicleData['ownerType']){
                case 'ORGANIZATION':
                    $arizachi = $vehicleData['name'];
                    break;
                case 'PERSON':
                    $arizachi = $vehicleData['firstName'] . " " . $vehicleData['lastName'] . " " . $vehicleData['middleName'];
                    break;
            }

            $phone = $ctx->phone;
            $sugurtaDavri = $ctx->startAt;
            $muddat =  $ctx->policeSeason;
            $sugurta_muddati = $muddat['days'];

            $tugash_sanasi = date('d.m.Y', strtotime($ctx->startAt . ' + ' . ($muddat['days'] - 1) . ' days'));

            $haydovchilar = '';
            if ($ctx->drivers != ''){
                foreach ($ctx->drivers as $driver){
                    $haydovchilar .= $driver['firstName'] .  ' ' . $driver['lastName'] .  ' - ' . $driver['seria'] .  ' ' . $driver['number'] . "\n";
                }
            }

            $service = new EuroAsiaService();
            $driverRestriction = $ctx->driverRestriction == 'Limited' ? true : false;
            $calcRequest = [
                'seasonalInsuranceId' => $muddat['id'],
                'driverRestriction' => $driverRestriction,
                'useTerritoryRegionId' => $ctx->vehicleData['useTerritoryRegionId'],
                'vehicleGroupId' => $ctx->vehicleData['vehicleGroupId'],
            ];
            $dto = $service->getCalculateOsagoDTO(
                [],
                $muddat['id'],
                $driverRestriction,
                $ctx->vehicleData['useTerritoryRegionId'],
                $ctx->vehicleData['vehicleGroupId']
            );
            $ctx->logApiCall('EuroAsiaService::getCalculateOsagoDTO', $calcRequest, $dto);

            if ($dto->success){
                $jami_summa = number_format((float)$dto->premium / 100, 0, '.', ' ');
            }else{
                $jami_summa = 'Aniqlanmadi!';
            }

            $text = sprintf($ctx->getMText("confirm texts"),
                $autoNumber,
                $texPass,
                $arizachi,
                $phone,
                $sugurtaDavri,
                $sugurta_muddati,
                $tugash_sanasi,
                $haydovchilar,
                $jami_summa
            );
            $ctx->sendMessageWithKeyborad($text, $option);

            $ctx->sendMessageWithID(EuroAsia::ORDER_CHANNEL_ID, $text);

        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }
    }

    public function handle(BotContext $ctx): void
    {
        if ($ctx->getKeywordText($ctx->text) != 'Continue ✅'){
            $text = $ctx->getMText('Please press one of the buttons.');
            $ctx->sendMessage($text);
            return;
        }

        if (is_null($ctx->police_data)){
            $this->mainMenu->show($ctx);
            return;
        }

        $botuser = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        $data = OsagoApplicationData::fromBotState($ctx);

        // See params.php 'osago.enableDirectEaiForTashkent' — off by default,
        // since the bot never took this path before this refactor (the
        // original code always queued to Gross regardless of plate prefix).
        $allowDirectEai = (bool)(Yii::$app->params['osago']['enableDirectEaiForTashkent'] ?? false);

        if ($allowDirectEai && $this->requestBuilder->isTashkentPlate($data->plateNumber)) {
            $ctx->sendMessageAdmin('Toshkent Avtomobili');
        }

        $result = $this->submissionService->submit($data, $botuser, $allowDirectEai);

        switch ($result->mode) {
            case 'gross':
                $ctx->sendMessageAdmin(json_encode($result->grossJobId, JSON_PRETTY_PRINT));
                $this->mainMenu->show($ctx, ['text' => $ctx->getMText("Your application has been accepted and we will insure you within 3 minutes!")]);
                break;
            case 'eai':
                $text = sprintf($ctx->getMText('Your insurance is ready'), $result->police->id, $result->paymentLink);
                $this->mainMenu->show($ctx, ['text' => $text]);
                break;
            default:
                $ctx->sendMessage('Nimadir xato boldi');
        }
    }
}
