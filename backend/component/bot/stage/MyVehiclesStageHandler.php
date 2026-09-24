<?php

namespace backend\component\bot\stage;

use backend\component\bot\BotContext;
use backend\component\bot\BotStageInterface;
use backend\component\insurance\VehicleLookupService;
use backend\ersp\ErspVehicleClient;
use backend\models\Pages;
use backend\queue\ErspLookupJob;
use common\models\Botuser;
use common\models\SavedVehicle;
use Yii;

/**
 * Pages::MY_VEHICLES, MY_VEHICLE_ADD_GOV_NUMBER, MY_VEHICLE_ADD_TEXPASS,
 * MY_VEHICLE_DETAIL, MY_VEHICLE_DELETE_CONFIRM — "🚗 Mening avtolarim"
 * bo'limi (hozircha faqat admin-only, BotCommandRouter/MainMenuStageHandler
 * orqali ochiladi): saqlangan avtomobillar ro'yxati, yangi avtomobil
 * qo'shish (EAI orqali tasdiqlab), avtomobil detali (keshlangan ERSP
 * sug'urta natijasi + qayta tekshirish/yangi sug'urta/o'chirish tugmalari,
 * o'chirish "Ha"/"Yo'q" tasdiqlovidan keyin bajariladi).
 *
 * Barcha matnlar `$ctx->getMText('keyword')`/`getKeywordText()` orqali
 * uz/ru tarjima qilinadi (kalitlar `console/migrations/
 * m260924_130000_insert_my_vehicles_text.php` orqali `text` jadvaliga
 * qo'shilgan) — boshqa stage-handlerlar bilan bir xil naqsh.
 */
class MyVehiclesStageHandler implements BotStageInterface
{
    public function __construct(
        private PhoneStageHandler $phoneStage
    ) {
    }

    public function show(BotContext $ctx, array $params = []): void
    {
        $page = $params['page'] ?? Pages::MY_VEHICLES;
        switch ($page) {
            case Pages::MY_VEHICLE_ADD_GOV_NUMBER:
                $this->showAddGovNumber($ctx);
                break;
            case Pages::MY_VEHICLE_ADD_TEXPASS:
                $this->showAddTexpass($ctx);
                break;
            case Pages::MY_VEHICLE_DETAIL:
                $this->showDetail($ctx);
                break;
            case Pages::MY_VEHICLE_DELETE_CONFIRM:
                $this->showDeleteConfirm($ctx);
                break;
            default:
                $this->showList($ctx);
        }
    }

    public function handle(BotContext $ctx): void
    {
        switch ($ctx->page) {
            case Pages::MY_VEHICLE_ADD_GOV_NUMBER:
                $this->handleAddGovNumber($ctx);
                break;
            case Pages::MY_VEHICLE_ADD_TEXPASS:
                $this->handleAddTexpass($ctx);
                break;
            case Pages::MY_VEHICLE_DETAIL:
                $this->handleDetail($ctx);
                break;
            case Pages::MY_VEHICLE_DELETE_CONFIRM:
                $this->handleDeleteConfirm($ctx);
                break;
            case Pages::MY_VEHICLES:
                $this->handleList($ctx);
                break;
        }
    }

    // ── RO'YXAT ──────────────────────────────────────────────────────────

    public function showList(BotContext $ctx): void
    {
        $ctx->page = Pages::MY_VEHICLES;

        $botuser  = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        $vehicles = $botuser ? $botuser->getSavedVehicles()->all() : [];

        // Davlat raqamlari 2 tadan bir qatorda joylashadi (ro'yxat uzun
        // bo'lganda klaviatura vertikal ravishda cho'zilib ketmasligi uchun).
        $option = [];
        $row    = [];
        foreach ($vehicles as $vehicle) {
            $row[] = $ctx->telegram->buildKeyboardButton($vehicle->gov_number);
            if (count($row) === 2) {
                $option[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $option[] = $row;
        }

        $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles add button'))];
        $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('Main menu'))];

        $text = $vehicles
            ? $ctx->getMText('my vehicles list title')
            : sprintf($ctx->getMText('my vehicles list empty'), $ctx->getMText('my vehicles add button'));

        $ctx->sendMessageWithKeyborad($text, $option);
    }

    private function handleList(BotContext $ctx): void
    {
        if ($ctx->getKeywordText($ctx->text) === 'my vehicles add button') {
            $this->showAddGovNumber($ctx);
            return;
        }

        $botuser = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        $vehicle = $botuser
            ? SavedVehicle::find()->where(['botuser_id' => $botuser->id, 'gov_number' => $ctx->text])->one()
            : null;

        if ($vehicle) {
            $ctx->selectedVehicleId = $vehicle->id;
            $this->showDetail($ctx);
            return;
        }

        $this->showList($ctx);
    }

    // ── AVTOMOBIL QO'SHISH ───────────────────────────────────────────────

    private function showAddGovNumber(BotContext $ctx): void
    {
        $ctx->page = Pages::MY_VEHICLE_ADD_GOV_NUMBER;

        $option = [[$ctx->telegram->buildKeyboardButton($ctx->getMText('Cancel'))]];
        $ctx->sendMessageWithKeyborad($ctx->getMText('ask lisence number'), $option);
    }

    private function handleAddGovNumber(BotContext $ctx): void
    {
        if (empty($ctx->text)) {
            $this->showAddGovNumber($ctx);
            return;
        }

        $ctx->newVehicleGovNumber = str_replace(' ', '', strtoupper($ctx->text));
        $this->showAddTexpass($ctx);
    }

    private function showAddTexpass(BotContext $ctx): void
    {
        $ctx->page = Pages::MY_VEHICLE_ADD_TEXPASS;

        $option = [[$ctx->telegram->buildKeyboardButton($ctx->getMText('Cancel'))]];
        $ctx->sendMessageWithKeyborad($ctx->getMText('ask texpasseria'), $option);
    }

    private function handleAddTexpass(BotContext $ctx): void
    {
        $value = strtoupper(trim((string) $ctx->text));
        $value = str_replace(' ', '', $value);

        if (!preg_match('/^([A-Z]{3})(\d{7})$/', $value, $matches)) {
            $this->showAddTexpass($ctx);
            return;
        }

        $govNumber = $ctx->newVehicleGovNumber;
        $seria     = $matches[1];
        $number    = $matches[2];

        $dto = (new VehicleLookupService())->lookup($seria, $number, $govNumber);
        $ctx->logApiCall('VehicleLookupService::lookup (my vehicles add)', [
            'techPassportSeria'  => $seria,
            'techPassportNumber' => $number,
            'licenseNumber'      => $govNumber,
        ], $dto);

        $ctx->newVehicleGovNumber = '';

        if (!$dto->success) {
            $ctx->sendMessage($ctx->getMText('my vehicles not found'));
            $this->showList($ctx);
            return;
        }

        $botuser = Botuser::findOne(['chat_id' => $ctx->chat_id]);
        if (!$botuser) {
            $this->showList($ctx);
            return;
        }

        $vehicle = SavedVehicle::find()
            ->where(['botuser_id' => $botuser->id, 'gov_number' => $govNumber])
            ->one() ?: new SavedVehicle([
                'botuser_id' => $botuser->id,
                'gov_number' => $govNumber,
            ]);

        $vehicle->tech_passport_seria  = $seria;
        $vehicle->tech_passport_number = $number;
        $vehicle->owner_type           = $dto->ownerType;
        $vehicle->model                = $dto->model;
        $vehicle->vehicle_type_name    = $dto->vehicleTypeName;
        $vehicle->save(false);

        $ctx->sendMessage(sprintf($ctx->getMText('my vehicles saved'), $govNumber));
        $this->showList($ctx);
    }

    // ── DETAL ────────────────────────────────────────────────────────────

    private function showDetail(BotContext $ctx): void
    {
        $ctx->page = Pages::MY_VEHICLE_DETAIL;

        $vehicle = SavedVehicle::findOne($ctx->selectedVehicleId);
        if (!$vehicle) {
            $this->showList($ctx);
            return;
        }

        $client         = new ErspVehicleClient();
        $activePolicies = $client->filterActivePolicies($vehicle->getCachedPolicies());

        $lines = ["🚗 {$vehicle->gov_number}"];

        if ($vehicle->ersp_check_status === SavedVehicle::ERSP_STATUS_CHECKING) {
            $lines[] = $ctx->getMText('my vehicles checking');
        } elseif ($activePolicies) {
            $lines[] = $ctx->getMText('my vehicles active policies title');
            $lines[] = implode("\n\n", array_map(
                fn(array $policy) => $this->formatPolicy($ctx, $client, $policy),
                $activePolicies
            ));
        } else {
            $lines[] = $ctx->getMText('my vehicles no policies');
        }

        if (!$vehicle->canCheckNow()) {
            $minutes = (int) ceil($vehicle->secondsUntilNextCheck() / 60);
            if ($minutes > 0) {
                $lines[] = sprintf($ctx->getMText('my vehicles next check in'), $minutes);
            }
        }

        $firstRow = [];
        if ($vehicle->canCheckNow()) {
            $firstRow[] = $ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles check button'));
        }
        $firstRow[] = $ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles new insurance button'));

        $option   = [$firstRow];
        $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles delete button'))];
        $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles back to list button'))];

        $ctx->sendMessageWithKeyborad(implode("\n", $lines), $option);
    }

    /**
     * Bitta polis uchun ko'p qatorli xabar bloki: kompaniya, seria/raqam,
     * qolgan muddat, tugash sanasi va PDF havolasi (sendWithKeyboard() HTML
     * parse_mode bilan yuboradi, shuning uchun <a href> ishlaydi).
     */
    private function formatPolicy(BotContext $ctx, ErspVehicleClient $client, array $policy): string
    {
        $company      = $policy['Sug‘urta kompaniya'] ?? $ctx->getMText('my vehicles unknown company');
        $series       = $policy['Polis seriyasi va raqami'] ?? '';
        $remaining    = $client->remainingLabel($policy) ?? $ctx->getMText('my vehicles unknown period');
        $expiresAt    = $client->endDateLabel($policy);
        $expiresLabel = $expiresAt ? date('d.m.Y', strtotime($expiresAt)) : $ctx->getMText('my vehicles unknown date');
        $expiresText  = $ctx->getMText('my vehicles policy expires label');

        $block = "🏢 {$company}\n📄 {$series}\n⏳ {$remaining}\n📅 {$expiresText} {$expiresLabel}";

        if (!empty($policy['pdf_link'])) {
            $linkText = $ctx->getMText('my vehicles view policy link');
            $block .= "\n🔗 <a href=\"{$policy['pdf_link']}\">{$linkText}</a>";
        }

        return $block;
    }

    private function handleDetail(BotContext $ctx): void
    {
        $vehicle = SavedVehicle::findOne($ctx->selectedVehicleId);
        if (!$vehicle) {
            $this->showList($ctx);
            return;
        }

        switch ($ctx->getKeywordText($ctx->text)) {
            case 'my vehicles check button':
                $this->triggerCheck($ctx, $vehicle);
                break;
            case 'my vehicles new insurance button':
                $this->startNewInsurance($ctx, $vehicle);
                break;
            case 'my vehicles delete button':
                $this->showDeleteConfirm($ctx);
                break;
            case 'my vehicles back to list button':
                $this->showList($ctx);
                break;
            default:
                $this->showDetail($ctx);
        }
    }

    private function showDeleteConfirm(BotContext $ctx): void
    {
        $vehicle = SavedVehicle::findOne($ctx->selectedVehicleId);
        if (!$vehicle) {
            $this->showList($ctx);
            return;
        }

        $ctx->page = Pages::MY_VEHICLE_DELETE_CONFIRM;

        $option = [
            [$ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles delete confirm button'))],
            [$ctx->telegram->buildKeyboardButton($ctx->getMText('my vehicles delete cancel button'))],
        ];

        $ctx->sendMessageWithKeyborad(
            sprintf($ctx->getMText('my vehicles delete confirm question'), $vehicle->gov_number),
            $option
        );
    }

    private function handleDeleteConfirm(BotContext $ctx): void
    {
        $vehicle = SavedVehicle::findOne($ctx->selectedVehicleId);
        if (!$vehicle) {
            $this->showList($ctx);
            return;
        }

        $keyword = $ctx->getKeywordText($ctx->text);

        if ($keyword === 'my vehicles delete confirm button') {
            $govNumber = $vehicle->gov_number;
            $vehicle->delete();
            $ctx->selectedVehicleId = '';

            $ctx->sendMessage(sprintf($ctx->getMText('my vehicles deleted'), $govNumber));
            $this->showList($ctx);
            return;
        }

        if ($keyword === 'my vehicles delete cancel button') {
            $this->showDetail($ctx);
            return;
        }

        $this->showDeleteConfirm($ctx);
    }

    private function triggerCheck(BotContext $ctx, SavedVehicle $vehicle): void
    {
        // "my vehicles check button" cooldown paytida showDetail()da
        // ko'rsatilmaydi — bu shunchaki qo'shimcha himoya (masalan eski
        // klaviatura orqali qayta bosilsa).
        if (!$vehicle->canCheckNow()) {
            $this->showDetail($ctx);
            return;
        }

        $vehicle->ersp_check_status = SavedVehicle::ERSP_STATUS_CHECKING;
        $vehicle->ersp_checked_at   = date('Y-m-d H:i:s');
        $vehicle->save(false);

        Yii::$app->erspQueue->push(new ErspLookupJob([
            'savedVehicleId' => $vehicle->id,
            'chatId'         => (string) $ctx->chat_id,
        ]));

        $ctx->sendMessage($ctx->getMText('my vehicles checking'));
        $this->showDetail($ctx);
    }

    /**
     * Davlat raqami/texpasport SO'RALMAYDI — saqlangan avtomobil
     * ma'lumotlari bilan EAI lookup PhoneStageHandler orqali fonda
     * davom etadi (VehicleLookupStageHandler::lookupAndProceed()).
     */
    private function startNewInsurance(BotContext $ctx, SavedVehicle $vehicle): void
    {
        $ctx->drivers           = '';
        $ctx->pendingDriver     = '';
        $ctx->ownerData         = '';
        $ctx->lisenceNumber     = '';
        $ctx->texPassSeria      = '';
        $ctx->texPassNumber     = '';
        $ctx->vehicleData       = '';
        $ctx->driverRestriction = '';
        $ctx->policeSeason      = '';
        $ctx->startAt           = '';
        $ctx->paymentType       = '';

        $ctx->pendingSavedVehicleId = $vehicle->id;

        $this->phoneStage->show($ctx);
    }
}
