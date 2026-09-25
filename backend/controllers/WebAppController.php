<?php

namespace backend\controllers;

use backend\component\EuroAsiaService;
use backend\component\insurance\DriverLookupService;
use backend\component\insurance\OrderChannelNotifier;
use backend\component\insurance\OsagoApplicationData;
use backend\component\insurance\OsagoSubmissionService;
use backend\component\insurance\OwnerLookupService;
use backend\component\insurance\PassportTextParser;
use backend\component\insurance\SeasonalInsuranceCatalog;
use backend\component\insurance\VehicleLookupService;
use backend\ersp\ErspVehicleClient;
use backend\queue\ErspLookupJob;
use common\models\Botuser;
use common\models\Police;
use common\models\SavedVehicle;
use common\models\Setting;
use common\models\Text;
use DateTime;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * JSON API backing the Telegram Mini App (backend/web/webapp).
 *
 * Reuses EuroAsiaService/Botuser/Police plus the shared
 * backend\component\insurance\* services (also used by BotController's
 * chat-based flow) for vehicle/owner/driver lookups and OSAGO submission,
 * without modifying BotController.php or backend\models\EuroAsia at all.
 */
class WebAppController extends Controller
{
    public $enableCsrfValidation = false;

    /** Raw request body cap — largest legitimate payload (vehicle+owner+drivers) is well under this. */
    private const MAX_BODY_BYTES = 65536;

    // Gross relative_type codes, per BotController.php's own documented mapping
    // (0=qarindosh emas, 1=ota, 2=ona, 3=er, 4=xotin, 5=o'g'il, 6=qiz, 7=aka, 8=uka, 9=opa, 10=singlisi).
    // Keys stay Uzbek (canonical) regardless of UI language — the front-end only
    // translates the *displayed* label, never the value it submits.
    private const RELATIVE_TYPES = [
        'Qarindosh emas' => 0,
        'Ota' => 1, 'Ona' => 2, 'Er' => 3, 'Xotin' => 4, "O'g'li" => 5,
        'Qizi' => 6, 'Aka' => 7, 'Uka' => 8, 'Opa' => 9, 'Singil' => 10,
    ];

    // Every user-facing message this controller can return, in uz/ru. Which
    // language is picked is resolved per-request from the caller's own bot
    // language setting (Botuser.data.lang) — see lang().
    private const MESSAGES = [
        'no_access' => [
            'uz' => "Ruxsat yo'q. Web App-ni botdan qayta oching",
            'ru' => "Нет доступа. Откройте Web App через бота ещё раз",
        ],
        'rate_limited' => [
            'uz' => "Juda ko'p so'rov yubordingiz. Birozdan keyin qayta urinib ko'ring",
            'ru' => "Слишком много запросов. Попробуйте немного позже",
        ],
        'fill_all_fields' => [
            'uz' => "Barcha maydonlarni to'ldiring",
            'ru' => "Заполните все поля",
        ],
        'vehicle_fetch_error' => [
            'uz' => "Transport ma'lumotlarini olishda xatolik yuz berdi",
            'ru' => "Ошибка при получении данных транспорта",
        ],
        'vehicle_not_found' => [
            'uz' => "Transport topilmadi. Ma'lumotlarni tekshirib qayta urinib ko'ring",
            'ru' => "Транспорт не найден. Проверьте данные и попробуйте снова",
        ],
        'owner_fetch_error' => [
            'uz' => "Egasi ma'lumotlarini olishda xatolik yuz berdi",
            'ru' => "Ошибка при получении данных владельца",
        ],
        'owner_not_found' => [
            'uz' => "Avtomobil egasi topilmadi. Pasport ma'lumotlarini tekshiring",
            'ru' => "Владелец автомобиля не найден. Проверьте паспортные данные",
        ],
        'driver_fetch_error' => [
            'uz' => "Haydovchi ma'lumotlarini olishda xatolik yuz berdi",
            'ru' => "Ошибка при получении данных водителя",
        ],
        'birthdate_invalid' => [
            'uz' => "Tug'ilgan sana noto'g'ri",
            'ru' => "Неверная дата рождения",
        ],
        'driver_not_found' => [
            'uz' => "Haydovchi topilmadi. Ma'lumotlarni tekshiring",
            'ru' => "Водитель не найден. Проверьте данные",
        ],
        'driver_license_not_found' => [
            'uz' => "Bu shaxsning haydovchilik guvohnomasi topilmadi",
            'ru' => "У этого человека не найдено водительское удостоверение",
        ],
        'duration_invalid' => [
            'uz' => "Sug'urta muddati noto'g'ri tanlangan",
            'ru' => "Неверно выбран срок страхования",
        ],
        'enter_vehicle_first' => [
            'uz' => "Avval transport ma'lumotlarini kiriting",
            'ru' => "Сначала введите данные транспорта",
        ],
        'calculate_error' => [
            'uz' => "Narxni hisoblashda xatolik yuz berdi",
            'ru' => "Ошибка при расчёте стоимости",
        ],
        'calculate_failed' => [
            'uz' => "Narxni aniqlab bo'lmadi",
            'ru' => "Не удалось рассчитать стоимость",
        ],
        'telegram_verify_failed' => [
            'uz' => "Telegram orqali tekshiruvdan o'ta olmadingiz. Web App-ni botdan qayta oching",
            'ru' => "Не удалось пройти проверку через Telegram. Откройте Web App через бота ещё раз",
        ],
        'already_submitted' => [
            'uz' => "Bu so'rov allaqachon yuborilgan. Web App-ni botdan qayta oching",
            'ru' => "Этот запрос уже был отправлен. Откройте Web App через бота ещё раз",
        ],
        'start_bot_first' => [
            'uz' => "Avval botga /start buyrug'ini yuboring",
            'ru' => "Сначала отправьте команду /start боту",
        ],
        'vehicle_data_incomplete' => [
            'uz' => "Transport ma'lumotlari to'liq emas",
            'ru' => "Данные транспорта заполнены не полностью",
        ],
        'insurance_type_choose' => [
            'uz' => "Sug'urta turini tanlang",
            'ru' => "Выберите тип страхования",
        ],
        'phone_incomplete' => [
            'uz' => "Telefon raqamini to'liq kiriting",
            'ru' => "Введите номер телефона полностью",
        ],
        'gateway_choose' => [
            'uz' => "To'lov turini tanlang",
            'ru' => "Выберите способ оплаты",
        ],
        'start_date_invalid' => [
            'uz' => "Boshlanish sanasi noto'g'ri",
            'ru' => "Неверная дата начала",
        ],
        'add_at_least_one_driver' => [
            'uz' => "Kamida bitta haydovchi qo'shing",
            'ru' => "Добавьте хотя бы одного водителя",
        ],
        'drivers_data_incomplete' => [
            'uz' => "Haydovchilar ma'lumoti to'liq emas",
            'ru' => "Данные водителей заполнены не полностью",
        ],
        'driver_birthdate_invalid' => [
            'uz' => "Haydovchi tug'ilgan sanasi noto'g'ri",
            'ru' => "Неверная дата рождения водителя",
        ],
        'owner_data_incomplete' => [
            'uz' => "Avtomobil egasi ma'lumotlari to'liq emas",
            'ru' => "Данные владельца автомобиля заполнены не полностью",
        ],
        'submitted_gross' => [
            'uz' => "Arizangiz qabul qilindi va 3 daqiqa ichida sug'urta qilinasiz! To'lov havolasi botga yuboriladi.",
            'ru' => "Ваша заявка принята, страховка будет оформлена в течение 3 минут! Ссылка на оплату придёт в бот.",
        ],
        'create_insurance_error' => [
            'uz' => "Sug'urta yaratishda xatolik yuz berdi. Iltimos qayta urinib ko'ring",
            'ru' => "Ошибка при оформлении страховки. Пожалуйста, попробуйте снова",
        ],
        'submitted_eai' => [
            'uz' => "Arizangiz qabul qilindi. 3 daqiqa ichida sizga to'lov havolasi yuboriladi.",
            'ru' => "Ваша заявка принята. В течение 3 минут вам придёт ссылка на оплату.",
        ],
        'submit_error' => [
            'uz' => "Ariza yuborishda xatolik yuz berdi. Iltimos qayta urinib ko'ring",
            'ru' => "Ошибка при отправке заявки. Пожалуйста, попробуйте снова",
        ],
        'invalid_format' => [
            'uz' => "Ma'lumot formati noto'g'ri",
            'ru' => "Неверный формат данных",
        ],
        'too_many_drivers' => [
            'uz' => "Ko'pi bilan 5 ta haydovchi qo'shishingiz mumkin",
            'ru' => "Можно добавить не более 5 водителей",
        ],
        'my_vehicles_disabled' => [
            'uz' => "\"Mening avtolarim\" bo'limi hozircha o'chirilgan",
            'ru' => "Раздел «Мои автомобили» пока отключён",
        ],
        'check_cooldown' => [
            'uz' => "Keyingi tekshirish %d daqiqadan keyin mumkin",
            'ru' => "Следующая проверка возможна через %d минут",
        ],
    ];

    /** @var array|null the input() call for the current action, kept for the admin audit log */
    private ?array $lastInput = null;
    /** @var array|null the verified Telegram user for the current action (null if unverified), kept for the admin audit log */
    private ?array $lastTelegramUser = null;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Manual check (not VerbFilter): this controller always answers in JSON,
        // and a VerbFilter-thrown MethodNotAllowedHttpException would go through
        // Yii's generic error handler instead of this JSON contract.
        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            Yii::$app->response->data = ['success' => false, 'message' => 'Method not allowed'];
            return false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Sends every Web App API call (endpoint, who, request, response) to the admin
     * as a Telegram message, mirroring BotController's own sendMessageAdmin() audit
     * trail pattern. Runs for every action regardless of outcome, so both normal use
     * and rejected/unauthenticated attempts are visible to the admin.
     */
    public function afterAction($action, $result)
    {
        $this->logToAdmin($action->id, $result);
        return parent::afterAction($action, $result);
    }

    /**
     * First call the Mini App makes on load: resolves which language (uz/ru) the
     * user has picked in the bot, so the front-end can render itself accordingly.
     * Always succeeds (defaults to 'uz') — this is a display preference only,
     * never a gate, so an unverified/first-time caller still gets a usable app.
     */
    public function actionInit()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);

        return [
            'success' => true,
            'lang' => $this->lang($telegramUser),
        ];
    }

    public function actionVehicle()
    {
        $input = $this->input();

        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $techSeria = strtoupper(trim((string)($input['techSeria'] ?? '')));
        $techNumber = trim((string)($input['techNumber'] ?? ''));
        $plateNumber = str_replace(' ', '', strtoupper(trim((string)($input['plateNumber'] ?? ''))));

        if ($techSeria === '' || $techNumber === '' || $plateNumber === '') {
            return $this->fail($this->msg('fill_all_fields', $lang));
        }
        if (!(new PassportTextParser())->looksLikeTechPassport($techSeria, $techNumber)) {
            return $this->fail($this->msg('invalid_format', $lang));
        }

        try {
            $dto = (new VehicleLookupService())->lookup($techSeria, $techNumber, $plateNumber);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('vehicle_fetch_error', $lang));
        }

        if (!$dto->success) {
            return $this->fail($this->msg('vehicle_not_found', $lang));
        }

        return [
            'success' => true,
            'ownerType' => $dto->ownerType,
            'inn' => $dto->inn,
            'name' => $dto->name,
            'firstName' => $dto->firstName,
            'middleName' => $dto->middleName,
            'lastName' => $dto->lastName,
            'pinfl' => $dto->pinfl,
            'birthDate' => $dto->birthDate,
            'useTerritoryRegionId' => $dto->useTerritoryRegionId,
            'vehicleGroupId' => $dto->vehicleGroupId,
            'model' => $dto->model,
            'vehicleTypeName' => $dto->vehicleTypeName,
        ];
    }

    public function actionOwner()
    {
        $input = $this->input();

        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $seria = strtoupper(trim((string)($input['seria'] ?? '')));
        $number = trim((string)($input['number'] ?? ''));
        $pinfl = trim((string)($input['pinfl'] ?? ''));

        if ($seria === '' || $number === '' || $pinfl === '') {
            return $this->fail($this->msg('fill_all_fields', $lang));
        }
        if (!(new PassportTextParser())->looksLikePersonPassport($seria, $number)) {
            return $this->fail($this->msg('invalid_format', $lang));
        }

        try {
            $dto = (new OwnerLookupService())->lookupByPinfl($seria, $number, $pinfl);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('owner_fetch_error', $lang));
        }

        if (!$dto->success) {
            return $this->fail($this->msg('owner_not_found', $lang));
        }

        return [
            'success' => true,
            'firstName' => $dto->firstName,
            'middleName' => $dto->middleName,
            'lastName' => $dto->lastName,
            'pinfl' => $dto->pinfl,
            'birthDate' => $dto->birthDate,
            'seria' => $dto->seria,
            'number' => $dto->number,
            'districtId' => $dto->districtId,
        ];
    }

    public function actionDriver()
    {
        $input = $this->input();

        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $seria = strtoupper(trim((string)($input['seria'] ?? '')));
        $number = trim((string)($input['number'] ?? ''));
        $birthDate = trim((string)($input['birthDate'] ?? ''));

        if ($seria === '' || $number === '' || $birthDate === '') {
            return $this->fail($this->msg('fill_all_fields', $lang));
        }
        if (!(new PassportTextParser())->looksLikePersonPassport($seria, $number)) {
            return $this->fail($this->msg('invalid_format', $lang));
        }

        $isoBirthdate = $this->ymdToIso($birthDate);
        if (!$isoBirthdate) {
            return $this->fail($this->msg('birthdate_invalid', $lang));
        }

        try {
            $dto = (new DriverLookupService())->lookup($seria, $number, $isoBirthdate);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('driver_fetch_error', $lang));
        }

        if (!$dto->success) {
            return $this->fail($this->msg('driver_not_found', $lang));
        }

        if (!$dto->driverLicense) {
            return $this->fail($this->msg('driver_license_not_found', $lang));
        }

        return [
            'success' => true,
            'firstName' => $dto->firstName,
            'middleName' => $dto->middleName,
            'lastName' => $dto->lastName,
        ];
    }

    public function actionCalculate()
    {
        $input = $this->input();

        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $seasonKey = (string)($input['duration'] ?? '');
        $season = (new SeasonalInsuranceCatalog())->byKey($seasonKey);
        if ($season === null) {
            return $this->fail($this->msg('duration_invalid', $lang));
        }

        $driverRestriction = (bool)($input['driverRestriction'] ?? false);
        $useTerritoryRegionId = (string)($input['useTerritoryRegionId'] ?? '');
        $vehicleGroupId = (string)($input['vehicleGroupId'] ?? '');

        if ($useTerritoryRegionId === '' || $vehicleGroupId === '') {
            return $this->fail($this->msg('enter_vehicle_first', $lang));
        }

        try {
            $dto = (new EuroAsiaService())->getCalculateOsagoDTO(
                [],
                $season['id'],
                $driverRestriction,
                $useTerritoryRegionId,
                $vehicleGroupId
            );
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('calculate_error', $lang));
        }

        if (!$dto->success) {
            return $this->fail($this->msg('calculate_failed', $lang));
        }

        return [
            'success' => true,
            'premium' => (float)$dto->premium / 100,
        ];
    }

    public function actionSubmit()
    {
        $input = $this->input();

        if (!empty($input['clientDebug'])) {
            $debugJson = json_encode($input['clientDebug'], JSON_UNESCAPED_UNICODE);
            if (mb_strlen($debugJson) > 2000) {
                $debugJson = mb_substr($debugJson, 0, 2000) . '...(qisqartirildi)';
            }
            Yii::warning('clientDebug: ' . $debugJson, 'webapp');
        }

        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('telegram_verify_failed', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        // A captured/replayed request (e.g. resent from Postman) carries the exact same
        // signed initData as the original — reject a second submit with the same signature
        // so it can't create duplicate policies/payment links.
        if (!$this->claimInitDataOnce((string)($input['initData'] ?? ''))) {
            return $this->fail($this->msg('already_submitted', $lang));
        }

        $botuser = Botuser::find()->where(['chat_id' => $telegramUser['id']])->one();
        if (!$botuser) {
            return $this->fail($this->msg('start_bot_first', $lang));
        }

        $plateNumber = str_replace(' ', '', strtoupper(trim((string)($input['plateNumber'] ?? ''))));
        $techSeria = strtoupper(trim((string)($input['techSeria'] ?? '')));
        $techNumber = trim((string)($input['techNumber'] ?? ''));
        $vehicleData = (array)($input['vehicleData'] ?? []);
        $ownerData = (array)($input['ownerData'] ?? []);
        $insuranceType = (string)($input['insuranceType'] ?? '');
        $phoneDigits = preg_replace('/\D/', '', (string)($input['phone'] ?? ''));
        $driversInput = (array)($input['drivers'] ?? []);
        $startDate = (string)($input['startDate'] ?? '');
        $durationKey = (string)($input['duration'] ?? '');
        $gateway = (string)($input['gateway'] ?? '');

        if ($plateNumber === '' || $techSeria === '' || $techNumber === '' || empty($vehicleData['ownerType'])) {
            return $this->fail($this->msg('vehicle_data_incomplete', $lang));
        }
        if (!(new PassportTextParser())->looksLikeTechPassport($techSeria, $techNumber)) {
            return $this->fail($this->msg('invalid_format', $lang));
        }
        if (!in_array($insuranceType, ['limited', 'unlimited'], true)) {
            return $this->fail($this->msg('insurance_type_choose', $lang));
        }
        if (strlen($phoneDigits) < 9) {
            return $this->fail($this->msg('phone_incomplete', $lang));
        }
        $season = (new SeasonalInsuranceCatalog())->byKey($durationKey);
        if ($season === null) {
            return $this->fail($this->msg('duration_invalid', $lang));
        }
        if (!in_array($gateway, ['CLICK', 'PAYME'], true)) {
            return $this->fail($this->msg('gateway_choose', $lang));
        }
        $startIso = $this->ymdToIso($startDate);
        if (!$startIso) {
            return $this->fail($this->msg('start_date_invalid', $lang));
        }

        $driverRestriction = $insuranceType === 'limited';

        if ($driverRestriction && empty($driversInput)) {
            return $this->fail($this->msg('add_at_least_one_driver', $lang));
        }
        if (count($driversInput) > 5) {
            return $this->fail($this->msg('too_many_drivers', $lang));
        }

        $drivers = [];
        foreach ($driversInput as $driver) {
            $dSeria = strtoupper(trim((string)($driver['seria'] ?? '')));
            $dNumber = trim((string)($driver['number'] ?? ''));
            $dBirth = trim((string)($driver['birthDate'] ?? ''));
            if ($dSeria === '' || $dNumber === '' || $dBirth === '') {
                return $this->fail($this->msg('drivers_data_incomplete', $lang));
            }
            if (!(new PassportTextParser())->looksLikePersonPassport($dSeria, $dNumber)) {
                return $this->fail($this->msg('invalid_format', $lang));
            }
            $dBirthIso = $this->ymdToIso($dBirth);
            if (!$dBirthIso) {
                return $this->fail($this->msg('driver_birthdate_invalid', $lang));
            }
            $relation = (string)($driver['relation'] ?? '');
            $drivers[] = [
                'seria' => $dSeria,
                'number' => $dNumber,
                'birthDateIso' => $dBirthIso,
                'relativeType' => self::RELATIVE_TYPES[$relation] ?? 0,
            ];
        }

        $isOrg = $vehicleData['ownerType'] === 'ORGANIZATION';

        if (!$isOrg && (empty($ownerData['seria']) || empty($ownerData['number']))) {
            return $this->fail($this->msg('owner_data_incomplete', $lang));
        }
        if (!$isOrg && !(new PassportTextParser())->looksLikePersonPassport($ownerData['seria'], $ownerData['number'])) {
            return $this->fail($this->msg('invalid_format', $lang));
        }

        $data = new OsagoApplicationData();
        $data->plateNumber = $plateNumber;
        $data->techSeria = $techSeria;
        $data->techNumber = $techNumber;
        $data->isOrganization = $isOrg;
        $data->organizationInn = $vehicleData['inn'] ?? '';
        if (!$isOrg) {
            $data->ownerSeria = $ownerData['seria'];
            $data->ownerNumber = $ownerData['number'];
            $data->ownerBirthDateIso = $ownerData['birthDate'] ?? null;
            $data->districtId = $ownerData['districtId'] ?? null;
        }
        $data->grossPhoneDigits9 = substr($phoneDigits, -9);
        $data->eaiPhoneNumber = '998' . substr($phoneDigits, -9);
        $data->driverRestriction = $driverRestriction;
        $data->startAtIso = $startIso;
        $data->seasonalInsuranceId = $season['id'];
        $data->periodType = $season['period_type'];
        $data->gateway = $gateway;
        $data->drivers = $drivers;

        $this->notifyOrderChannel($lang, $plateNumber, $techSeria, $techNumber, $vehicleData, $data, $season, $driverRestriction, $startDate, $driversInput);

        try {
            // false: all submissions, Tashkent plates included, go through the Gross
            // queue — the Mini App used to take a direct-EAI path for Tashkent plates,
            // but that's no longer wanted; now it matches the bot's own behavior.
            $result = Yii::createObject(OsagoSubmissionService::class)->submit($data, $botuser, false);

            if ($result->mode === 'gross') {
                return [
                    'success' => true,
                    'mode' => 'gross',
                    'message' => $this->msg('submitted_gross', $lang),
                ];
            }

            if ($result->mode === 'eai') {
                $this->notifyUser($botuser, $result->police, $result->paymentLink);

                return [
                    'success' => true,
                    'mode' => 'eai',
                    'message' => $this->msg('submitted_eai', $lang),
                    'policeId' => $result->police->id,
                ];
            }

            return $this->fail($this->msg('create_insurance_error', $lang));
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('submit_error', $lang));
        }
    }

    /**
     * Botning ConfirmStageHandler::show() bilan bir xil formatdagi xabarni
     * "orders" kanaliga yuboradi (OrderChannelNotifier — "confirm texts"
     * andozasi), "🌐 Web App orqali" prefiksi bilan. Haqiqiy arizani hech
     * qachon to'xtatmasligi uchun o'z try/catch'iga o'ralgan — bu shunchaki
     * bildirishnoma, submission oqimining bir qismi emas.
     */
    private function notifyOrderChannel(
        string $lang,
        string $plateNumber,
        string $techSeria,
        string $techNumber,
        array $vehicleData,
        OsagoApplicationData $data,
        array $season,
        bool $driverRestriction,
        string $startDate,
        array $driversInput
    ): void {
        try {
            $arizachi = $vehicleData['ownerType'] === 'ORGANIZATION'
                ? (string)($vehicleData['name'] ?? '')
                : trim(($vehicleData['firstName'] ?? '') . ' ' . ($vehicleData['lastName'] ?? '') . ' ' . ($vehicleData['middleName'] ?? ''));

            $driversText = '';
            foreach ($driversInput as $driver) {
                $name = trim((string)($driver['name'] ?? ''));
                $dSeria = strtoupper(trim((string)($driver['seria'] ?? '')));
                $dNumber = trim((string)($driver['number'] ?? ''));
                $driversText .= "{$name} - {$dSeria} {$dNumber}\n";
            }

            $premiumLabel = 'Aniqlanmadi!';
            try {
                $calcDto = (new EuroAsiaService())->getCalculateOsagoDTO(
                    [],
                    $season['id'],
                    $driverRestriction,
                    (string)($vehicleData['useTerritoryRegionId'] ?? ''),
                    (string)($vehicleData['vehicleGroupId'] ?? '')
                );
                if ($calcDto->success) {
                    $premiumLabel = number_format((float)$calcDto->premium / 100, 0, '.', ' ');
                }
            } catch (\Throwable $e) {
                Yii::error('OrderChannelNotifier calculate: ' . $e->getMessage(), 'webapp');
            }

            $startDateLabel = date('d.m.Y', strtotime($startDate));
            $endDateLabel = date('d.m.Y', strtotime($startDate . ' + ' . ($season['days'] - 1) . ' days'));

            Yii::createObject(OrderChannelNotifier::class)->notify(
                $lang,
                $plateNumber,
                $techSeria . $techNumber,
                $arizachi,
                $data->eaiPhoneNumber,
                $startDateLabel,
                $season['days'],
                $endDateLabel,
                $driversText,
                $premiumLabel,
                true
            );
        } catch (\Throwable $e) {
            Yii::error('OrderChannelNotifier: ' . $e->getMessage(), 'webapp');
        }
    }

    // ── "MENING AVTOLARIM" ───────────────────────────────────────────────
    //
    // Bu beshta action ataylab requireTelegramUser()'dan tashqari
    // Setting::getMyVehiclesStatus()ni ham tekshiradi — global yoqilgan/
    // o'chirilgan bayroq (Admin panel → Bot sozlamalari → "Mening avtolarim
    // holati"), admin-only emas: yoqilganda HAR QANDAY tasdiqlangan Telegram
    // foydalanuvchisi (actionVehicle()/actionOwner()/... bilan bir xil)
    // foydalana oladi, o'chirilganda esa hech kimga (admin ham) ochiq emas.
    // Serverda tekshiriladi — faqat interfeysda tugma/havolani yashirish
    // yetarli emas (havola oshkor bo'lib qolsa ham).

    public function actionMyVehiclesList()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!Setting::getMyVehiclesStatus()) {
            return $this->fail($this->msg('my_vehicles_disabled', $lang));
        }

        $botuser = Botuser::findOne(['chat_id' => $telegramUser['id']]);
        $vehicles = $botuser ? $botuser->getSavedVehicles()->orderBy(['id' => SORT_DESC])->all() : [];

        return [
            'success' => true,
            'vehicles' => array_map([$this, 'vehicleSummary'], $vehicles),
        ];
    }

    public function actionMyVehiclesAdd()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!Setting::getMyVehiclesStatus()) {
            return $this->fail($this->msg('my_vehicles_disabled', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $techSeria = strtoupper(trim((string)($input['techSeria'] ?? '')));
        $techNumber = trim((string)($input['techNumber'] ?? ''));
        $govNumber = str_replace(' ', '', strtoupper(trim((string)($input['govNumber'] ?? ''))));

        if ($techSeria === '' || $techNumber === '' || $govNumber === '') {
            return $this->fail($this->msg('fill_all_fields', $lang));
        }
        if (!(new PassportTextParser())->looksLikeTechPassport($techSeria, $techNumber)) {
            return $this->fail($this->msg('invalid_format', $lang));
        }

        try {
            $dto = (new VehicleLookupService())->lookup($techSeria, $techNumber, $govNumber);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail($this->msg('vehicle_fetch_error', $lang));
        }

        if (!$dto->success) {
            return $this->fail($this->msg('vehicle_not_found', $lang));
        }

        $botuser = Botuser::findOne(['chat_id' => $telegramUser['id']]);
        if (!$botuser) {
            return $this->fail($this->msg('start_bot_first', $lang));
        }

        $vehicle = SavedVehicle::find()
            ->where(['botuser_id' => $botuser->id, 'gov_number' => $govNumber])
            ->one() ?: new SavedVehicle([
                'botuser_id' => $botuser->id,
                'gov_number' => $govNumber,
            ]);

        $vehicle->tech_passport_seria = $techSeria;
        $vehicle->tech_passport_number = $techNumber;
        $vehicle->owner_type = $dto->ownerType;
        $vehicle->model = $dto->model;
        $vehicle->vehicle_type_name = $dto->vehicleTypeName;
        $vehicle->save(false);

        return [
            'success' => true,
            'vehicle' => $this->vehicleSummary($vehicle),
        ];
    }

    /**
     * ERSP'ga so'rov YUBORMAYDI — faqat `saved_vehicle` jadvalidagi oxirgi
     * "✅ Tekshirish"dan qolgan keshni o'qiydi (§4/§5 rejasidagi kabi).
     */
    public function actionMyVehicleDetail()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!Setting::getMyVehiclesStatus()) {
            return $this->fail($this->msg('my_vehicles_disabled', $lang));
        }

        $vehicle = $this->findOwnSavedVehicle($input, $telegramUser);
        if (!$vehicle) {
            return $this->fail($this->msg('vehicle_not_found', $lang));
        }

        $client = new ErspVehicleClient();
        $activePolicies = $client->filterActivePolicies($vehicle->getCachedPolicies());

        return [
            'success' => true,
            'vehicle' => $this->vehicleSummary($vehicle),
            'checkStatus' => $vehicle->ersp_check_status,
            'checkedAt' => $vehicle->ersp_checked_at,
            'canCheck' => $vehicle->canCheckNow(),
            'nextCheckInSeconds' => $vehicle->secondsUntilNextCheck(),
            'policies' => array_map(function (array $policy) use ($client) {
                return [
                    'company' => $policy['Sug‘urta kompaniya'] ?? null,
                    'seriesNumber' => $policy['Polis seriyasi va raqami'] ?? null,
                    'expiresAt' => $client->endDateLabel($policy),
                    'remainingLabel' => $client->remainingLabel($policy),
                    'pdfLink' => $policy['pdf_link'] ?? null,
                ];
            }, $activePolicies),
        ];
    }

    public function actionMyVehicleCheck()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!Setting::getMyVehiclesStatus()) {
            return $this->fail($this->msg('my_vehicles_disabled', $lang));
        }
        if (!$this->rateLimitOk($telegramUser['id'])) {
            return $this->fail($this->msg('rate_limited', $lang));
        }

        $vehicle = $this->findOwnSavedVehicle($input, $telegramUser);
        if (!$vehicle) {
            return $this->fail($this->msg('vehicle_not_found', $lang));
        }

        if (!$vehicle->canCheckNow()) {
            $minutes = (int) ceil($vehicle->secondsUntilNextCheck() / 60);
            return $this->fail(sprintf($this->msg('check_cooldown', $lang), max(1, $minutes)));
        }

        $vehicle->ersp_check_status = SavedVehicle::ERSP_STATUS_CHECKING;
        $vehicle->ersp_checked_at = date('Y-m-d H:i:s');
        $vehicle->save(false);

        Yii::$app->erspQueue->push(new ErspLookupJob([
            'savedVehicleId' => $vehicle->id,
            'chatId' => (string) $telegramUser['id'],
        ]));

        return ['success' => true, 'checking' => true];
    }

    /**
     * Tasdiqlash frontendda ("O'chirish" bosilganda Telegram showConfirm/
     * window.confirm orqali so'raladi) — bu action shunchaki bajaradi,
     * qayta so'ramaydi.
     */
    public function actionMyVehicleDelete()
    {
        $input = $this->input();
        $telegramUser = $this->requireTelegramUser($input);
        $lang = $this->lang($telegramUser);
        if (!$telegramUser) {
            return $this->fail($this->msg('no_access', $lang));
        }
        if (!Setting::getMyVehiclesStatus()) {
            return $this->fail($this->msg('my_vehicles_disabled', $lang));
        }

        $vehicle = $this->findOwnSavedVehicle($input, $telegramUser);
        if (!$vehicle) {
            return $this->fail($this->msg('vehicle_not_found', $lang));
        }

        $vehicle->delete();

        return ['success' => true];
    }

    private function vehicleSummary(SavedVehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'govNumber' => $vehicle->gov_number,
            'techPassportSeria' => $vehicle->tech_passport_seria,
            'techPassportNumber' => $vehicle->tech_passport_number,
            'model' => $vehicle->model,
            'vehicleTypeName' => $vehicle->vehicle_type_name,
        ];
    }

    /** Faqat chaqiruvchi Telegram foydalanuvchisiga tegishli saqlangan avtomobilni qaytaradi (boshqa botuserning yozuviga id orqali kirib bo'lmaydi). */
    private function findOwnSavedVehicle(array $input, array $telegramUser): ?SavedVehicle
    {
        $vehicleId = (int)($input['vehicleId'] ?? 0);
        if ($vehicleId <= 0) {
            return null;
        }

        $botuser = Botuser::findOne(['chat_id' => $telegramUser['id']]);
        if (!$botuser) {
            return null;
        }

        return SavedVehicle::find()
            ->where(['id' => $vehicleId, 'botuser_id' => $botuser->id])
            ->one();
    }

    private function notifyUser(Botuser $botuser, Police $police, string $paymentLink): void
    {
        $lang = $this->userLang($botuser);

        $record = Text::findOne(['keyword' => 'Your insurance is ready']);
        $text = ($record && $record->$lang)
            ? sprintf($record->$lang, $police->id, $paymentLink)
            : sprintf(
                $lang === 'ru'
                    ? "ID: %s Ваша страховка готова! Перейдите по ссылке ниже, чтобы произвести оплату.\n%s"
                    : "ID: %s Sug'urtangiz tayyor! Pastdagi havola orqali to'lovni amalga oshiring.\n%s",
                $police->id,
                $paymentLink
            );

        Yii::$app->telegram->sendMessage([
            'chat_id' => $botuser->chat_id,
            'parse_mode' => 'html',
            'text' => $text,
        ]);
    }

    /**
     * initData stays identical for the whole Mini App session (every step reuses it),
     * so it can't be blocked as "used" on first sight — only actionSubmit (the one
     * action with real side effects: policy + payment link) claims it here, once,
     * right before doing anything else. Yii::$app->cache->add() only succeeds the
     * first time a given key is set, giving atomic single-use semantics without a
     * DB migration. TTL matches verifyInitData()'s own 24h freshness window, since a
     * hash can never be re-validated as fresh past that point anyway.
     */
    private function claimInitDataOnce(string $initData): bool
    {
        parse_str($initData, $parsed);
        $hash = $parsed['hash'] ?? null;
        if (!$hash) {
            return false;
        }
        return Yii::$app->cache->add('webapp_submit_' . $hash, 1, 90000);
    }

    /**
     * Basic per-user abuse guard for the read-only lookup endpoints (vehicle/owner/
     * driver/calculate), which a valid-but-malicious admin session could otherwise use
     * to bulk-enumerate plate numbers or passport series+numbers against EAI's PII API.
     * Shares one counter across all four endpoints per chat_id so it can't be bypassed
     * by spreading requests across them.
     */
    private function rateLimitOk($chatId, int $max = 30, int $windowSeconds = 60): bool
    {
        $key = 'webapp_rl_' . $chatId;
        $count = Yii::$app->cache->get($key);
        if ($count === false) {
            Yii::$app->cache->set($key, 1, $windowSeconds);
            return true;
        }
        if ($count >= $max) {
            Yii::warning("Web App: chat_id {$chatId} tezlik chegarasidan oshdi", 'webapp');
            return false;
        }
        Yii::$app->cache->set($key, $count + 1, $windowSeconds);
        return true;
    }

    /**
     * Every action must call this: verifies the caller is a genuine Telegram user via
     * HMAC-signed initData. The Web App's inline button is admin-only for now (gated
     * in BotController), but this check intentionally does NOT also require admin —
     * once the button is shown to regular bot users, their requests must keep working.
     * Without this check at all, the lookup endpoints would be an unauthenticated PII
     * oracle (vehicle/passport/driver data) reachable by anyone on the internet; this
     * still closes that hole, since forging a valid signature requires the bot token.
     */
    private function requireTelegramUser(array $input): ?array
    {
        $telegramUser = $this->verifyInitData((string)($input['initData'] ?? ''));
        $this->lastTelegramUser = $telegramUser;
        return $telegramUser;
    }

    /**
     * Resolves uz/ru for the calling Telegram user, from the same Botuser.data.lang
     * value BotController's own getMText()/lang property reads (set via the bot's
     * "Language selection" menu) — so the Mini App always matches the language the
     * user already chose in the chat, with no separate preference to keep in sync.
     */
    private function lang(?array $telegramUser): string
    {
        if (!$telegramUser || empty($telegramUser['id'])) {
            return 'uz';
        }
        $botuser = Botuser::find()->select(['data'])->where(['chat_id' => $telegramUser['id']])->one();
        return $this->userLang($botuser);
    }

    private function userLang(?Botuser $botuser): string
    {
        if (!$botuser || !$botuser->data) {
            return 'uz';
        }
        $data = json_decode($botuser->data, true);
        return ($data['lang'] ?? 'uz') === 'ru' ? 'ru' : 'uz';
    }

    private function msg(string $key, string $lang): string
    {
        return self::MESSAGES[$key][$lang] ?? self::MESSAGES[$key]['uz'] ?? $key;
    }

    private function verifyInitData(string $initData): ?array
    {
        if ($initData === '') {
            Yii::warning('initData bo\'sh keldi (Telegram.WebApp.initData mijozda to\'ldirilmagan)', 'webapp');
            return null;
        }

        parse_str($initData, $parsed);
        if (!isset($parsed['hash'])) {
            Yii::warning('initData ichida hash yo\'q: ' . $initData, 'webapp');
            return null;
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);
        ksort($parsed);

        $pairs = [];
        foreach ($parsed as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $pairs);

        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        if (!$botToken) {
            Yii::warning('TELEGRAM_BOT_TOKEN topilmadi (getenv bo\'sh)', 'webapp');
            return null;
        }

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            Yii::warning(
                "initData hash mos kelmadi.\ndataCheckString: {$dataCheckString}\nkutilgan: {$calculatedHash}\nkelgan: {$hash}",
                'webapp'
            );
            return null;
        }

        if (isset($parsed['auth_date']) && (time() - (int)$parsed['auth_date']) > 86400) {
            Yii::warning('initData eskirgan (auth_date 24 soatdan katta)', 'webapp');
            return null;
        }

        $user = isset($parsed['user']) ? json_decode($parsed['user'], true) : null;
        if (!$user || empty($user['id'])) {
            Yii::warning('initData ichida user.id topilmadi', 'webapp');
            return null;
        }

        return $user;
    }

    private function ymdToIso(string $ymd): ?string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $ymd);
        if (!$dt || $dt->format('Y-m-d') !== $ymd) {
            return null;
        }
        return $dt->format('Y-m-d\TH:i:s.v\Z');
    }

    private function input(): array
    {
        $raw = Yii::$app->request->getRawBody();
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            // Oversized body: treat as empty rather than throw — each action's
            // existing "field is missing" checks already reject this cleanly,
            // keeping every response inside the same JSON contract.
            $this->lastInput = [];
            return [];
        }
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        $input = is_array($decoded) ? $decoded : (array)Yii::$app->request->post();
        $this->lastInput = $input;
        return $input;
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }

    private function logToAdmin(string $actionId, $result): void
    {
        try {
            $user = $this->lastTelegramUser;
            if ($user) {
                $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $who = ($name !== '' ? $name : 'ismsiz') . " (chat_id: {$user['id']}"
                    . (!empty($user['username']) ? ", @{$user['username']}" : '') . ')';
            } else {
                $who = "tasdiqlanmagan (initData yaroqsiz yoki bo'sh)";
            }

            $logInput = (array)$this->lastInput;
            unset($logInput['initData'], $logInput['clientDebug']);

            $text = "🌐 <b>Web App so'rovi</b>\n"
                . 'Endpoint: <code>' . htmlspecialchars($actionId, ENT_QUOTES, 'UTF-8') . "</code>\n"
                . 'Foydalanuvchi: ' . htmlspecialchars($who, ENT_QUOTES, 'UTF-8') . "\n\n"
                . "So'rov:\n<pre>" . htmlspecialchars(
                    json_encode($logInput, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    ENT_QUOTES,
                    'UTF-8'
                ) . "</pre>\n\n"
                . "Javob:\n<pre>" . htmlspecialchars(
                    json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    ENT_QUOTES,
                    'UTF-8'
                ) . '</pre>';

            if (mb_strlen($text) > 3900) {
                $text = mb_substr($text, 0, 3900) . "\n… (qisqartirildi)";
            }

            Yii::$app->telegram->sendMessage([
                'chat_id' => BotController::ADMIN_ID,
                'parse_mode' => 'html',
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Yii::error('Admin webapp logini yuborishda xato: ' . $e->getMessage(), 'webapp');
        }
    }
}
