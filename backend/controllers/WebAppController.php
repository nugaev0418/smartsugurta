<?php

namespace backend\controllers;

use backend\component\EuroAsiaService;
use common\models\Botuser;
use common\models\Police;
use common\models\SeasonalInsurance;
use common\models\Text;
use backend\queue\GrossOsagoJob;
use DateTime;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * JSON API backing the Telegram Mini App (backend/web/webapp).
 *
 * Reuses EuroAsiaService/Botuser/Police/SeasonalInsurance/GrossOsagoJob exactly the
 * way BotController's chat-based flow does, without modifying BotController.php
 * or backend\models\EuroAsia at all.
 */
class WebAppController extends Controller
{
    public $enableCsrfValidation = false;

    private const SEASONS = [
        '1y' => ['id' => '8465a831-850f-4445-a995-ef71195094ab', 'days' => 365, 'period_type' => 7],
        '6m' => ['id' => '9848096e-cc12-4dbd-893b-41f2cdfc9a0e', 'days' => 180, 'period_type' => 1],
        '20d' => ['id' => '0d546748-0ba6-43bc-9ce2-1b977ad9e494', 'days' => 20, 'period_type' => 8],
    ];

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionVehicle()
    {
        $input = $this->input();

        if (!$this->requireTelegramUser($input)) {
            return $this->fail("Ruxsat yo'q. Web App-ni botdan qayta oching");
        }

        $techSeria = strtoupper(trim((string)($input['techSeria'] ?? '')));
        $techNumber = trim((string)($input['techNumber'] ?? ''));
        $plateNumber = str_replace(' ', '', strtoupper(trim((string)($input['plateNumber'] ?? ''))));

        if ($techSeria === '' || $techNumber === '' || $plateNumber === '') {
            return $this->fail("Barcha maydonlarni to'ldiring");
        }

        try {
            $dto = (new EuroAsiaService())->getVehicleOwnerDTO($techSeria, $techNumber, $plateNumber);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail("Transport ma'lumotlarini olishda xatolik yuz berdi");
        }

        if (!$dto->success) {
            return $this->fail("Transport topilmadi. Ma'lumotlarni tekshirib qayta urinib ko'ring");
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
        ];
    }

    public function actionOwner()
    {
        $input = $this->input();

        if (!$this->requireTelegramUser($input)) {
            return $this->fail("Ruxsat yo'q. Web App-ni botdan qayta oching");
        }

        $seria = strtoupper(trim((string)($input['seria'] ?? '')));
        $number = trim((string)($input['number'] ?? ''));
        $pinfl = trim((string)($input['pinfl'] ?? ''));

        if ($seria === '' || $number === '' || $pinfl === '') {
            return $this->fail("Barcha maydonlarni to'ldiring");
        }

        try {
            $dto = (new EuroAsiaService())->getPersonByPinflDTO($seria, $number, $pinfl);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail("Egasi ma'lumotlarini olishda xatolik yuz berdi");
        }

        if (!$dto->success) {
            return $this->fail("Avtomobil egasi topilmadi. Pasport ma'lumotlarini tekshiring");
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

        if (!$this->requireTelegramUser($input)) {
            return $this->fail("Ruxsat yo'q. Web App-ni botdan qayta oching");
        }

        $seria = strtoupper(trim((string)($input['seria'] ?? '')));
        $number = trim((string)($input['number'] ?? ''));
        $birthDate = trim((string)($input['birthDate'] ?? ''));

        if ($seria === '' || $number === '' || $birthDate === '') {
            return $this->fail("Barcha maydonlarni to'ldiring");
        }

        $isoBirthdate = $this->ymdToIso($birthDate);
        if (!$isoBirthdate) {
            return $this->fail("Tug'ilgan sana noto'g'ri");
        }

        try {
            $dto = (new EuroAsiaService())->getPersonByBirthdateDTO($seria, $number, $isoBirthdate);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail("Haydovchi ma'lumotlarini olishda xatolik yuz berdi");
        }

        if (!$dto->success) {
            return $this->fail("Haydovchi topilmadi. Ma'lumotlarni tekshiring");
        }

        if (!$dto->driverLicense) {
            return $this->fail("Bu shaxsning haydovchilik guvohnomasi topilmadi");
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

        if (!$this->requireTelegramUser($input)) {
            return $this->fail("Ruxsat yo'q. Web App-ni botdan qayta oching");
        }

        $seasonKey = (string)($input['duration'] ?? '');
        if (!isset(self::SEASONS[$seasonKey])) {
            return $this->fail("Sug'urta muddati noto'g'ri tanlangan");
        }
        $season = self::SEASONS[$seasonKey];

        $driverRestriction = (bool)($input['driverRestriction'] ?? false);
        $useTerritoryRegionId = (string)($input['useTerritoryRegionId'] ?? '');
        $vehicleGroupId = (string)($input['vehicleGroupId'] ?? '');

        if ($useTerritoryRegionId === '' || $vehicleGroupId === '') {
            return $this->fail("Avval transport ma'lumotlarini kiriting");
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
            return $this->fail("Narxni hisoblashda xatolik yuz berdi");
        }

        if (!$dto->success) {
            return $this->fail("Narxni aniqlab bo'lmadi");
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
            Yii::warning('clientDebug: ' . json_encode($input['clientDebug'], JSON_UNESCAPED_UNICODE), 'webapp');
        }

        $telegramUser = $this->requireTelegramUser($input);
        if (!$telegramUser) {
            return $this->fail("Telegram orqali tekshiruvdan o'ta olmadingiz. Web App-ni botdan qayta oching");
        }

        $botuser = Botuser::find()->where(['chat_id' => $telegramUser['id']])->one();
        if (!$botuser) {
            return $this->fail("Avval botga /start buyrug'ini yuboring");
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
            return $this->fail("Transport ma'lumotlari to'liq emas");
        }
        if (!in_array($insuranceType, ['limited', 'unlimited'], true)) {
            return $this->fail("Sug'urta turini tanlang");
        }
        if (strlen($phoneDigits) < 9) {
            return $this->fail("Telefon raqamini to'liq kiriting");
        }
        if (!isset(self::SEASONS[$durationKey])) {
            return $this->fail("Sug'urta muddati noto'g'ri tanlangan");
        }
        if (!in_array($gateway, ['CLICK', 'PAYME'], true)) {
            return $this->fail("To'lov turini tanlang");
        }
        $startIso = $this->ymdToIso($startDate);
        if (!$startIso) {
            return $this->fail("Boshlanish sanasi noto'g'ri");
        }

        $driverRestriction = $insuranceType === 'limited';

        if ($driverRestriction && empty($driversInput)) {
            return $this->fail("Kamida bitta haydovchi qo'shing");
        }

        $eaiDrivers = [];
        $grossDrivers = [];
        foreach ($driversInput as $driver) {
            $dSeria = strtoupper(trim((string)($driver['seria'] ?? '')));
            $dNumber = trim((string)($driver['number'] ?? ''));
            $dBirth = trim((string)($driver['birthDate'] ?? ''));
            if ($dSeria === '' || $dNumber === '' || $dBirth === '') {
                return $this->fail("Haydovchilar ma'lumoti to'liq emas");
            }
            $dBirthIso = $this->ymdToIso($dBirth);
            if (!$dBirthIso) {
                return $this->fail("Haydovchi tug'ilgan sanasi noto'g'ri");
            }
            $eaiDrivers[] = [
                'passportBirthdate' => $dBirthIso,
                'passportNumber' => $dNumber,
                'passportSeria' => $dSeria,
                'relativeId' => '',
            ];
            $grossDrivers[] = [
                'document' => $dSeria . $dNumber,
                'birth_date' => $dBirth,
                'relative_type' => 0,
            ];
        }

        $season = self::SEASONS[$durationKey];
        $fullPhone = '998' . substr($phoneDigits, -9);

        $vehicle = [
            'licenseNumber' => $plateNumber,
            'techPassportNumber' => $techNumber,
            'techPassportSeria' => $techSeria,
        ];

        $isOrg = $vehicleData['ownerType'] === 'ORGANIZATION';

        if ($isOrg) {
            $owner = [
                'isInsurant' => true,
                'type' => 'ORGANIZATION',
                'organization' => ['inn' => $vehicleData['inn'] ?? ''],
            ];
            $insurant = [
                'type' => 'ORGANIZATION',
                'phoneNumber' => $fullPhone,
                'organization' => ['inn' => $vehicleData['inn'] ?? ''],
            ];
        } else {
            if (empty($ownerData['seria']) || empty($ownerData['number'])) {
                return $this->fail("Avtomobil egasi ma'lumotlari to'liq emas");
            }
            $owner = [
                'isInsurant' => false,
                'type' => 'PERSON',
                'person' => [
                    'passportNumber' => $ownerData['number'],
                    'passportSeria' => $ownerData['seria'],
                ],
            ];
            $insurant = [
                'type' => 'PERSON',
                'phoneNumber' => $fullPhone,
                'person' => [
                    'passportNumber' => $ownerData['number'],
                    'passportSeria' => $ownerData['seria'],
                    'passportBirthdate' => $ownerData['birthDate'] ?? null,
                ],
                'districtId' => $ownerData['districtId'] ?? null,
            ];
        }

        $eaiData = [
            'vehicle' => $vehicle,
            'owner' => $owner,
            'insurant' => $insurant,
            'drivers' => $eaiDrivers,
            'billingGateway' => $gateway,
            'driverRestriction' => $driverRestriction,
            'seasonalInsuranceId' => $season['id'],
            'startAt' => $startIso,
        ];

        $prefix = substr($plateNumber, 0, 2);
        $isTashkent = in_array($prefix, ['01', '10'], true);

        try {
            if (!$isTashkent) {
                $grossOwner = ['is_org' => $isOrg];
                if (!$isOrg) {
                    $grossOwner['passport'] = $ownerData['seria'] . $ownerData['number'];
                }

                $policyDataGross = [
                    'phone' => substr($phoneDigits, -9),
                    'vehicle' => [
                        'gov_number' => $plateNumber,
                        'seria' => $techSeria,
                        'number' => $techNumber,
                    ],
                    'owner' => $grossOwner,
                    'policy_type' => $driverRestriction ? 'limited' : 'unlimited',
                    'start_date' => $startDate,
                    'period_type' => $season['period_type'],
                    'drivers' => $grossDrivers,
                ];

                Yii::$app->grossQueue->push(new GrossOsagoJob([
                    'policyDataGross' => $policyDataGross,
                    'policyDataEAI' => $eaiData,
                    'chat_id' => $botuser->chat_id,
                ]));

                return [
                    'success' => true,
                    'mode' => 'gross',
                    'message' => "Arizangiz qabul qilindi va 3 daqiqa ichida sug'urta qilinasiz! To'lov havolasi botga yuboriladi.",
                ];
            }

            $dto = (new EuroAsiaService())->createOsagoDTO($eaiData);
            if (!$dto->success) {
                return $this->fail("Sug'urta yaratishda xatolik yuz berdi. Iltimos qayta urinib ko'ring");
            }

            $seasonModel = SeasonalInsurance::find()->where(['seasonId' => $season['id']])->one();

            $police = new Police();
            $police->policeId = $dto->policyId;
            $police->user_id = $botuser->id;
            $police->startAt = date('Y-m-d', strtotime($startDate));
            $police->paymentLink = $dto->paymentLink;
            $police->paymentId = $dto->paymentId;
            $police->gateway = $gateway;
            $police->amount = 0;
            $police->driverRestriction = $driverRestriction ? 1 : 0;
            $police->season_id = $seasonModel->id ?? null;
            $police->provider_id = Police::PROVIDER_EAI;
            $police->save(false);

            $this->notifyUser($botuser, $police, $dto->paymentLink);

            return [
                'success' => true,
                'mode' => 'eai',
                'paymentLink' => $dto->paymentLink,
                'policeId' => $police->id,
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'webapp');
            return $this->fail("Ariza yuborishda xatolik yuz berdi. Iltimos qayta urinib ko'ring");
        }
    }

    private function notifyUser(Botuser $botuser, Police $police, string $paymentLink): void
    {
        $data = $botuser->data ? json_decode($botuser->data, true) : [];
        $lang = $data['lang'] ?? 'uz';

        $record = Text::findOne(['keyword' => 'Your insurance is ready']);
        $text = ($record && $record->$lang)
            ? sprintf($record->$lang, $police->id, $paymentLink)
            : sprintf(
                "ID: %s Sug'urtangiz tayyor! Pastdagi havola orqali to'lovni amalga oshiring.\n%s",
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
        return $this->verifyInitData((string)($input['initData'] ?? ''));
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
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : (array)Yii::$app->request->post();
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
