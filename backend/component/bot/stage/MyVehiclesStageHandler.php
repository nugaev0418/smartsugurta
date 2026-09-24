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
 */
class MyVehiclesStageHandler implements BotStageInterface
{
    private const BTN_ADD            = "➕ Avtomobil qo'shish";
    private const BTN_BACK_TO_LIST   = "⬅️ Ro'yxatga qaytish";
    private const BTN_CHECK          = "Tekshirish";
    private const BTN_NEW_INSURANCE  = "Yangi sug'urta qilish";
    private const BTN_DELETE         = "🗑 O'chirish";
    private const BTN_DELETE_CONFIRM = "✅ Ha, o'chirish";
    private const BTN_DELETE_CANCEL  = "❌ Yo'q, bekor qilish";

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

        $option = [];
        foreach ($vehicles as $vehicle) {
            $option[] = [$ctx->telegram->buildKeyboardButton($vehicle->gov_number)];
        }
        $option[] = [$ctx->telegram->buildKeyboardButton(self::BTN_ADD)];
        $option[] = [$ctx->telegram->buildKeyboardButton($ctx->getMText('Main menu'))];

        $text = $vehicles
            ? "Saqlangan avtomobillaringiz:"
            : "Hali avtomobil saqlanmagan. \"" . self::BTN_ADD . "\" orqali qo'shing.";

        $ctx->sendMessageWithKeyborad($text, $option);
    }

    private function handleList(BotContext $ctx): void
    {
        if ($ctx->text === self::BTN_ADD) {
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
            $ctx->sendMessage("Avtomobil topilmadi, ma'lumotlarni tekshirib qayta urinib ko'ring.");
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

        $ctx->sendMessage("✅ {$govNumber} avtomobili saqlandi.");
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
            $lines[] = "🔎 Tekshirilmoqda, natija tez orada shu yerga yuboriladi...";
        } elseif ($activePolicies) {
            $lines[] = "Amaldagi sug'urtalar:";
            foreach ($activePolicies as $policy) {
                $lines[] = '• ' . ($client->remainingLabel($policy) ?? "muddat noma'lum");
            }
        } else {
            $lines[] = "Amaldagi sug'urta topilmadi yoki hali tekshirilmagan.";
        }

        if (!$vehicle->canCheckNow()) {
            $minutes = (int) ceil($vehicle->secondsUntilNextCheck() / 60);
            if ($minutes > 0) {
                $lines[] = "⏳ Keyingi tekshirish {$minutes} daqiqadan keyin mumkin.";
            }
        }

        $option = [];
        if ($vehicle->canCheckNow()) {
            $option[] = [$ctx->telegram->buildKeyboardButton(self::BTN_CHECK)];
        }
        $option[] = [$ctx->telegram->buildKeyboardButton(self::BTN_NEW_INSURANCE)];
        $option[] = [$ctx->telegram->buildKeyboardButton(self::BTN_DELETE)];
        $option[] = [$ctx->telegram->buildKeyboardButton(self::BTN_BACK_TO_LIST)];

        $ctx->sendMessageWithKeyborad(implode("\n", $lines), $option);
    }

    private function handleDetail(BotContext $ctx): void
    {
        $vehicle = SavedVehicle::findOne($ctx->selectedVehicleId);
        if (!$vehicle) {
            $this->showList($ctx);
            return;
        }

        switch ($ctx->text) {
            case self::BTN_CHECK:
                $this->triggerCheck($ctx, $vehicle);
                break;
            case self::BTN_NEW_INSURANCE:
                $this->startNewInsurance($ctx, $vehicle);
                break;
            case self::BTN_DELETE:
                $this->showDeleteConfirm($ctx);
                break;
            case self::BTN_BACK_TO_LIST:
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
            [$ctx->telegram->buildKeyboardButton(self::BTN_DELETE_CONFIRM)],
            [$ctx->telegram->buildKeyboardButton(self::BTN_DELETE_CANCEL)],
        ];

        $ctx->sendMessageWithKeyborad(
            "⚠️ Rostdan ham {$vehicle->gov_number} avtomobilini ro'yxatdan o'chirmoqchimisiz?",
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

        if ($ctx->text === self::BTN_DELETE_CONFIRM) {
            $govNumber = $vehicle->gov_number;
            $vehicle->delete();
            $ctx->selectedVehicleId = '';

            $ctx->sendMessage("🗑 {$govNumber} avtomobili ro'yxatdan o'chirildi.");
            $this->showList($ctx);
            return;
        }

        if ($ctx->text === self::BTN_DELETE_CANCEL) {
            $this->showDetail($ctx);
            return;
        }

        $this->showDeleteConfirm($ctx);
    }

    private function triggerCheck(BotContext $ctx, SavedVehicle $vehicle): void
    {
        // BTN_CHECK cooldown paytida showDetail()da ko'rsatilmaydi — bu shunchaki
        // qo'shimcha himoya (masalan eski klaviatura orqali qayta bosilsa).
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

        $ctx->sendMessage("🔎 Tekshirilmoqda, natija tez orada shu yerga yuboriladi...");
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
