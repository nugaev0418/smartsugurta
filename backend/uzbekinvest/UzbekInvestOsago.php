<?php

namespace backend\uzbekinvest;

use RuntimeException;


/**
 * OSAGO jarayonidagi xatolik — qaysi bosqichda (step) va qanday qo'shimcha
 * ma'lumot (payload) bilan yuz berganini saqlaydi.
 */
class UzbekOsagoException extends RuntimeException
{
    private string  $step;
    private ?array  $payload;

    public function __construct(string $message, string $step = '', ?array $payload = null)
    {
        parent::__construct($message);
        $this->step    = $step;
        $this->payload = $payload;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }
}

/**
 * UzbekInvest (APEX savdo platformasi) ustidagi yuqori darajadagi xizmat.
 *
 * createPolicy() ga faqat kirish ma'lumotlari beriladi — butun OSAGO polisini
 * yaratish oqimi (mashina qidirish → egasi → haydovchilar → koeffitsientlar →
 * muddat → mukofot → submit → to'lov URLlari) shu yerda bajariladi.
 *
 * Har bir bosqich tekshiriladi; xato bo'lsa natija sifatida
 *   ['success' => false, 'error' => ..., 'step' => ..., 'payload' => ...]
 * qaytariladi. $options['throw_on_error'] = true bo'lsa, xuddi shu ma'lumot
 * bilan UzbekOsagoException ham throw qilinadi.
 */
class UzbekInvestOsago
{
    private UzbekInvestClient $client;
    private array       $options;
    private ?string     $logDir = null;
    private int         $logSeq = 0;
    private string      $runId = '';
    private string      $session = '';
    private array       $warnings = [];

    /**
     * $options kalitlari (default qiymatlari):
     *   base_url        => 'https://sales-platform.uzbekinvest.uz'
     *   storage_dir     => __DIR__            (cookies.txt / session.txt joyi)
     *   log             => true               (logs/<vaqt>/ papkasiga yozish)
     *   log_root        => __DIR__ . '/logs'
     *   throw_on_error  => false              (true — xatoda exception throw)
     *   dry_run         => false              (true — submit qilinmaydi)
     */
    public function __construct(string $username, string $password, array $options = [])
    {
        $this->options = array_replace([
            'base_url'       => 'https://sales-platform.uzbekinvest.uz',
            'storage_dir'    => __DIR__,
            'log'            => true,
            'log_root'       => __DIR__ . '/logs',
            'throw_on_error' => false,
            'dry_run'        => false,
            'verify_ssl'     => true,
            'db_log'         => false, // true: har qadam gross_api_log ("Job logs") ga yoziladi (faqat Yii ichida)
            'chat_id'        => 0,
        ], $options);

        $this->client = new UzbekInvestClient(
            $username,
            $password,
            $this->options['storage_dir'],
            $this->options['base_url'],
            (bool)$this->options['verify_ssl']
        );
    }

    /**
     * Yii::$app->params['uzbekinvest'] dan konfiguratsiya yig'adi:
     *   login, password (majburiy); baseUrl, verifySsl, log (ixtiyoriy).
     * $profile — faqat log uchun ("queue" yozadi, "check" yozmaydi). Cookie/session papkasi
     * hamma jarayon uchun umumiy (sharedStorageDir()).
     * Xatoda UzbekOsagoException throw qilinadi.
     */
    public static function fromParams(string $profile = 'queue', int $chatId = 0): self
    {
        $cfg  = \Yii::$app->params['uzbekinvest'];
        $root = \Yii::getAlias('@runtime/uzbekinvest');

        $options = [
            'storage_dir'    => self::sharedStorageDir(),
            'log'            => (bool)($cfg['log'] ?? false),
            'log_root'       => $root . '/logs',
            'throw_on_error' => true,
            'verify_ssl'     => (bool)($cfg['verifySsl'] ?? true),
            // faqat polisa yaratuvchi navbat yoziladi; to'lov tekshiruvi (cron) logni to'ldirmasin
            'db_log'         => $profile === 'queue',
            'chat_id'        => $chatId,
        ];
        if (!empty($cfg['baseUrl'])) {
            $options['base_url'] = $cfg['baseUrl'];
        }

        return new self($cfg['login'], $cfg['password'], $options);
    }

    /**
     * Navbat job uchun: polisani yaratadi va xatoda UzbekOsagoException
     * throw qiladi (natija massivi o'rniga). Qaytaradi:
     *   contract_id, premium, pay_urls (click/payme/uzum/paynet), redirect_url.
     */
    public function run(array $policyData): array
    {
        $this->options['throw_on_error'] = true;
        $result = $this->createPolicy($policyData);

        if (empty($result['contract_id'])) {
            throw new UzbekOsagoException('Polis saqlandi, lekin shartnoma raqami aniqlanmadi', 'contract', $result);
        }

        return $result;
    }

    /**
     * Shartnoma to'langan va tasdiqlanganmi (portfeldagi qator holati
     * "Тасдиқланган" va PDF havolasi bor). To'langan bo'lsa ersp.e-osgo.uz
     * polis UUID'i (pdf_url ichidan) ham qaytariladi.
     *
     * @return array{paid: bool, status: ?string, uuid: ?string, pdf_url: ?string}
     */
    public function checkPayment(string $contractId): array
    {
        $this->options['throw_on_error'] = true;
        $result = $this->getPolicies([
            'P100_CONTRACT_ID' => $contractId,
            'P100_PERIOD'      => date('d.m.Y', strtotime('-30 days')) . ' - ' . date('d.m.Y'),
        ]);

        foreach ($result['policies'] as $p) {
            if (($p['contract_id'] ?? null) !== $contractId) {
                continue;
            }
            $confirmed = isset($p['status'])
                && mb_stripos($p['status'], 'тасдиқланган') !== false
                && mb_stripos($p['status'], 'тасдиқланмаган') === false;
            $uuid = null;
            if (!empty($p['pdf_url']) && preg_match('/[?&]id=([0-9a-f\-]{36})/i', $p['pdf_url'], $m)) {
                $uuid = $m[1];
            }

            return [
                'paid'    => $confirmed && $uuid !== null,
                'status'  => $p['status'] ?? null,
                'uuid'    => $uuid,
                'pdf_url' => $p['pdf_url'] ?? null,
            ];
        }

        return ['paid' => false, 'status' => null, 'uuid' => null, 'pdf_url' => null];
    }

    /**
     * Barcha jarayonlar (navbat worker'i - console, to'lov cron'i - backend, login-test) UCHUN BITTA
     * umumiy cookie/session papkasi: sayt bir akkauntda faqat bitta faol sessiyaga ruxsat beradi,
     * shuning uchun har jarayon o'z sessiyasini ochsa, ular bir-birini o'chirib turadi.
     * `@console/runtime` ishlatiladi, chunki u ikkala ilovadan (backend, console) bir xil yo'l.
     */
    public static function sharedStorageDir(): string
    {
        $cfg = \Yii::$app->params['uzbekinvest'] ?? [];

        return $cfg['storageDir'] ?? (\Yii::getAlias('@console') . '/runtime/uzbekinvest/shared');
    }

    /**
     * Client chaqiruvi; sessiya o'rtada bekor bo'lgan bo'lsa ("Сессия муддати тугади" yoki login
     * sahifasi qaytsa) bir marta qayta kirib, o'sha chaqiruvni takrorlaydi.
     */
    private function api(string $method, mixed ...$args): mixed
    {
        $result = $this->client->$method($this->session, ...$args);
        if (!$this->isSessionExpired($result)) {
            return $result;
        }

        $this->session = $this->client->refreshSession($this->session);

        return $this->client->$method($this->session, ...$args);
    }

    private function isSessionExpired(mixed $result): bool
    {
        if (is_array($result)) {
            return isset($result['error']) && is_string($result['error']) && mb_stripos($result['error'], 'Сессия') !== false;
        }

        return is_string($result) && str_contains($result, 'P9999_USERNAME');
    }

    /** Oxirgi ish yozilgan log papkasi (log o'chirilgan bo'lsa null). */
    public function lastLogDir(): ?string
    {
        return $this->logDir;
    }

    // ===================================================================
    // OSAGO POLISINI YARATISH
    // ===================================================================

    /**
     * Kirish ma'lumotlari (majburiy):
     *   tech_sery, tech_number, gov_number — texnik pasport + davlat raqami
     *   phone                              — ariza beruvchi telefoni
     *   start_date (DD.MM.YYYY), period    — sug'urta boshlanishi va muddat
     *   drivers                            — [['passport','birthdate'], ...] 1..5 ta
     * Ixtiyoriy (default):
     *   policy_type='2', use_territory_id='2', is_foreign='1',
     *   tech_date='18.06.2021' (fallback), vehicle_type='6' (fallback),
     *   owner_passport='', owner_birthdate='' (jismoniy egada majburiy)
     */
    public function createPolicy(array $input): array
    {
        $this->warnings = [];
        $this->beginLog();

        // --- 0. Kirishni tekshirish ---
        // policy_type: '2' = Чекланган (cheklangan, haydovchilar ro'yxati bilan),
        //              '1' = Чекланмаган (cheklanmagan, haydovchisiz)
        $isLimited = (string)($input['policy_type'] ?? '2') !== '1';
        $input['drivers'] = $input['drivers'] ?? [];

        $required = ['tech_sery', 'tech_number', 'gov_number', 'phone', 'start_date', 'period'];
        if ($isLimited) {
            $required[] = 'drivers';
        }
        $missing  = [];
        foreach ($required as $key) {
            if (!isset($input[$key]) || $input[$key] === '' || $input[$key] === []) {
                $missing[] = $key;
            }
        }
        if ($missing) {
            return $this->fail('input', 'Quyidagi majburiy ma\'lumotlar berilmagan: ' . implode(', ', $missing));
        }
        if (!is_array($input['drivers']) || ($isLimited && count($input['drivers']) === 0)) {
            return $this->fail('input', 'Kamida 1 ta haydovchi berilishi kerak (drivers)');
        }
        if (count($input['drivers']) > 5) {
            return $this->fail('input', 'Ko\'pi bilan 5 ta haydovchi qo\'llab-quvvatlanadi (drivers)');
        }
        foreach ($input['drivers'] as $i => $d) {
            if (empty($d['passport']) || empty($d['birthdate'])) {
                return $this->fail('input', "Haydovchi #" . ($i + 1) . " da passport va birthdate ko'rsatilishi shart");
            }
        }

        $in = array_replace([
            'policy_type'       => '2',
            'use_territory_id'  => '2',
            'is_foreign'        => '1', // 1=Маҳаллий transport, 2=Харбий, 3=Чет эл
            'tech_date'         => '18.06.2021',   // javobda bo'lmasa fallback
            'vehicle_type'      => '6',            // javobda bo'lmasa fallback
            'owner_passport'    => '',
            'owner_birthdate'   => '',
        ], $input);

        // --- 1. Session ---
        try {
            $this->session = $this->client->getSession();
        } catch (\RuntimeException $e) {
            return $this->fail('session', 'Tizimga kirish xatosi: ' . $e->getMessage());
        }

        // --- 2. Mashina ma'lumotlari ---
        $vehicle = $this->api('getVehicleInfo', $in['tech_sery'], $in['tech_number'], $in['gov_number']);
        $this->logStep('getVehicleInfo', $vehicle);
        if (!is_array($vehicle) || ($vehicle['status'] ?? '') !== 'success'
            || (int)($vehicle['error_code'] ?? -1) !== 0
            || !array_key_exists('owner_type', $vehicle)) {
            $msg = is_array($vehicle) && !empty($vehicle['message'])
                ? $vehicle['message']
                : 'Transport vosita ma\'lumotlari topilmadi';
            return $this->fail('vehicle', $msg, is_array($vehicle) ? $vehicle : null);
        }
        if (empty($vehicle['vehicle_type'])) {
            $this->warnings[] = "vehicle_type javobda yo'q — fallback: {$in['vehicle_type']} ishlatiladi";
        }
        if (empty($vehicle['tech_date'])) {
            $this->warnings[] = "tech_date javobda yo'q — fallback: {$in['tech_date']} ishlatiladi";
        }

        $isOrgVehicle = (int)($vehicle['owner_type'] ?? 0) === 1;

        // --- 3. Egani (ariza beruvchi) aniqlash ---
        // owner_type=1 → yuridik tashkilot (GET_ORG_BY_INN)
        // aks holda     → jismoniy shaxs   (GET_PERSON_BY_BIRTH)
        $ownerOrOrg = null;
        $pluginWarn = null;

        if ($isOrgVehicle) {
            $inn = $vehicle['inn'] ?? $vehicle['tin'] ?? '';
            if ($inn === '') {
                return $this->fail('owner', 'Tashkilot INN raqami topilmadi', $vehicle);
            }
            $orgOwner = $this->api('getOrgByInn', $inn, (string)$vehicle['owner_type']);
            $this->logStep('getOrgByInn', $orgOwner);
            if (!is_array($orgOwner) || ($orgOwner['status'] ?? '') !== 'success'
                || (int)($orgOwner['error_code'] ?? -1) !== 0
                || empty($orgOwner['org_name'])) {
                $msg = is_array($orgOwner) && !empty($orgOwner['message'])
                    ? $orgOwner['message']
                    : 'Tashkilot ma\'lumotlari topilmadi';
                return $this->fail('owner', $msg, is_array($orgOwner) ? $orgOwner : null);
            }

            // Yuridik turga o'tkazuvchi DA — xato bo'lsa ham to'xtamaymiz
            $pluginTokens = $this->api('extractPluginTokens');
            if (!empty($pluginTokens)) {
                $pluginResp = $this->api('pluginWithItems', $pluginTokens[0], [
                    'P252_APPLICANT_IS_ORGANIZATION' => '1',
                    'P252_IS_ORGANIZATION_HID'       => '',
                ]);
                $this->logStep('pluginSwitchToOrg', $pluginResp);
                $pluginOk = is_array($pluginResp)
                    && (($pluginResp['success'] ?? false) === true || ($pluginResp['status'] ?? '') === 'success');
                if (!$pluginOk) {
                    $pluginWarn = 'Yuridik shaxs rejimiga o\'tishda xato — davom etiladi';
                }
            } else {
                $pluginWarn = 'PLUGIN tokenlari topilmadi — yuridik rejimga o\'tish o\'tkazib yuborildi';
            }
            $ownerOrOrg = $orgOwner;
        } else {
            if ($in['owner_passport'] === '' || $in['owner_birthdate'] === '') {
                return $this->fail('owner', 'Jismoniy egasi uchun owner_passport va owner_birthdate berilishi shart');
            }
            $owner = $this->api('getPersonByBirth', $in['owner_birthdate'], $in['owner_passport']);
            $this->logStep('getPersonByBirth', $owner);
            // Bu javobda error_code YO'Q — faqat status va pinfl tekshiriladi
            if (!is_array($owner) || ($owner['status'] ?? '') !== 'success' || empty($owner['pinfl'])) {
                $msg = is_array($owner) && !empty($owner['message'])
                    ? $owner['message']
                    : 'Egasining ma\'lumotlari topilmadi';
                return $this->fail('owner', $msg, is_array($owner) ? $owner : null);
            }
            $ownerOrOrg = $owner;
        }
        if ($pluginWarn) {
            $this->warnings[] = $pluginWarn;
        }

        // --- 4. Haydovchilar + koeffitsientlar (har biridan keyin) ---
        $coefSlots  = ['FIRST', 'SECOND', 'THIRST', 'FORTY', 'FIVE'];
        $driverList = [];

        foreach ($in['drivers'] as $i => $d) {
            $n = $i + 1;

            $driver = $this->api('getDriverByBirth', $d['birthdate'], $d['passport']);
            $this->logStep('getDriverByBirth_' . $n, $driver);
            if (!is_array($driver) || ($driver['status'] ?? '') !== 'success'
                || (int)($driver['error_code'] ?? -1) !== 0
                || empty($driver['currentpinfl'])) {
                $msg = is_array($driver) && !empty($driver['message'])
                    ? $driver['message']
                    : "Haydovchi #{$n} ma'lumotlari topilmadi";
                return $this->fail('driver_' . $n, $msg, is_array($driver) ? $driver : null);
            }
            $driverList[] = $driver;

            // Shu paytgacha to'plangan haydovchilar bilan koeffitsient hisoblash
            $coefItems = [
                'P252_POLICY_TYPE' => $in['policy_type'],
                'P252_DR_COUNT'    => count($driverList),
            ];
            foreach ($coefSlots as $j => $slot) {
                $dr = $driverList[$j] ?? null;
                $coefItems['P252_DR_PINFL_' . $slot]       = $dr['currentpinfl'] ?? '';
                $coefItems['P252_DR_RESIDENT_' . $slot]    = '1';
                $coefItems['P252_DR_COEFFICIENT_' . $slot] = $dr['coefficient']  ?? ($dr ? '1' : '');
            }
            $coefResult = $this->api('calculateDriverCoef', $coefItems);
            $this->logStep('calculateDriverCoef_' . $n, $coefResult);
            if (!is_array($coefResult) || ($coefResult['success'] ?? false) !== true) {
                return $this->fail('coefficient_' . $n, "Haydovchi #{$n} koeffitsientini hisoblab bo'lmadi", $coefResult);
            }
        }

        // --- 5. Muddat tugash sanasi ---
        $periodResult = $this->api('calculatePeriodEndDate', $in['start_date'], $in['period']);
        $this->logStep('calculatePeriodEndDate', $periodResult);
        if (!is_array($periodResult) || ($periodResult['success'] ?? false) !== true || empty($periodResult['endDate'])) {
            return $this->fail('period', 'Muddat tugash sanasini hisoblab bo\'lmadi', $periodResult);
        }

        // --- 6. Mukofot hisoblash ---
        $premiumResult = $this->api('calculateOsagoPremium', [
            'P252_VEHICLE_TYPE'            => $vehicle['vehicle_type'] ?? $in['vehicle_type'],
            'P252_VEHICLE_TYPE_HID'        => $vehicle['vehicle_type'] ?? $in['vehicle_type'],
            'P252_POLICY_TYPE'             => $in['policy_type'],
            'P252_USE_TERITORY_ID'         => $in['use_territory_id'],
            'P252_IS_ACCIDENT_COIFFICIENT' => '0',
            'P252_IS_PESIONER'             => '0',
            'P252_PERIOD'                  => $in['period'],
        ]);
        $this->logStep('calculateOsagoPremium', $premiumResult);
        if (!is_array($premiumResult) || ($premiumResult['success'] ?? false) !== true
            || !array_key_exists('premium_numeric', $premiumResult)) {
            return $this->fail('premium', 'Sug\'urta mukofotini hisoblab bo\'lmadi', $premiumResult);
        }

        // --- 7. Submit maydonlarini yig'ish ---
        $fields = $this->buildSubmitFields($in, $vehicle, $ownerOrOrg, $driverList, $premiumResult);

        // Dry-run rejimi — polis YARATILMAYDI, faqat tayyor maydonlar qaytariladi
        if ($this->options['dry_run']) {
            $this->logStep('submit_dry_run', $fields);
            $result = [
                'success'         => true,
                'dry_run'         => true,
                'vehicle'         => $vehicle,
                'owner'           => $ownerOrOrg,
                'drivers'         => $driverList,
                'premium'         => $premiumResult['premium_numeric'] ?? null,
                'premium_formatted' => $premiumResult['premium'] ?? '',
                'period_end'      => $periodResult['endDate'] ?? '',
                'warnings'        => $this->warnings,
                'submit_params'   => $fields,
            ];
            $result['log_dir'] = $this->options['log'] ? $this->logDir : null;
            return $result;
        }

        // --- 8. Submit (polisni saqlash) ---
        if ($this->options['log']) {
            $this->client->debugDir = $this->logDir;
        }
        $submitResp = $this->api('submitOsago', $fields);
        $this->logStep('submitOsago', $submitResp);

        if (empty($submitResp['redirectURL'])) {
            if (!empty($submitResp['errors'][0]['message'])) {
                $err  = $submitResp['errors'][0]['message'];
                $page = $submitResp['errors'][0]['pageItem'] ?? '';
                return $this->fail('submit', $err . ($page ? " ({$page})" : ''), $submitResp);
            }
            if (!empty($submitResp['error'])) {
                return $this->fail('submit', $submitResp['error'], $submitResp);
            }
            return $this->fail('submit', 'Polis saqlanmadi — serverdan kutilmagan javob', $submitResp);
        }

        // --- 9. Shartnoma sahifasi + to'lov URLlari (fatal emas) ---
        $redirectUrl = $this->toAbsolute($submitResp['redirectURL']);
        $contractId  = $this->parseContractId($redirectUrl);
        $payUrls     = ['click' => null, 'payme' => null, 'uzum' => null, 'paynet' => null];

        $contractHtml = $this->api('fetchContractPage', $redirectUrl);
        if (!$contractHtml) {
            $this->warnings[] = 'Shartnoma sahifasini yuklab bo\'lmadi — polis saqlangan, lekin to\'lov URLlari olinmadi';
        } else {
            if ($this->options['log'] && $this->logDir) {
                file_put_contents($this->logDir . '/contract.html', $contractHtml);
            }
            $payUrls = $this->extractPayUrls($contractHtml);
            $this->logStep('pay_urls', $payUrls);
            if (!array_filter($payUrls)) {
                $this->warnings[] = 'To\'lov URLlari sahifada topilmadi';
            }
        }

        $result = [
            'success'           => true,
            'run_id'            => $this->runId,
            'contract_id'       => $contractId,
            'redirect_url'      => $redirectUrl,
            'pay_urls'          => $payUrls,
            'premium'           => $premiumResult['premium_numeric'] ?? null,
            'premium_formatted' => $premiumResult['premium'] ?? '',
            'period_end'        => $periodResult['endDate'] ?? '',
            'vehicle'           => $vehicle,
            'owner'             => $ownerOrOrg,
            'drivers'           => $driverList,
            'warnings'          => $this->warnings,
        ];
        $result['log_dir'] = $this->options['log'] ? $this->logDir : null;
        return $result;
    }

    // ===================================================================
    // POLISLAR RO'YXATI (PORTFEL)
    // ===================================================================

    /**
     * Portfeldan polislarni qidirish. $filters — P100_* APEX maydon nomlari
     * bilan beriladi va defaultlar ustiga qo'yiladi.
     */
    public function getPolicies(array $filters = []): array
    {
        $this->beginLog();

        try {
            $this->session = $this->client->getSession();
        } catch (\RuntimeException $e) {
            return $this->fail('policies', 'Tizimga kirish xatosi: ' . $e->getMessage());
        }

        $params = array_replace([
            'P100_INSURANCE_PRODUCT' => '0',
            'P100_PERIOD'            => '01.04.2026 - ' . date('d.m.Y'),
            'P100_CONTRACT_ID'       => '',
            'P100_POLICY_SERY'       => '',
            'P100_POLICY_NUMBER'     => '',
            'P100_TECH_SERY'         => '',
            'P100_TECH_NUMBER'       => '',
            'P100_TECH_GOVNUMBER'    => '',
            'P100_PASPORT'           => '',
            'P100_PINFL'             => '',
        ], $filters);

        $html = $this->api('searchPolicies', $params);
        if (!$html) {
            return $this->fail('policies', 'Polislar ro\'yxatini yuklab bo\'lmadi');
        }

        if ($this->options['log'] && $this->logDir) {
            file_put_contents($this->logDir . '/polices.html', $html);
        }

        $policies = $this->parsePoliciesTable($html);
        if ($policies === null) {
            return $this->fail('policies', 'Polislar jadvali sahifada topilmadi', ['html' => $html]);
        }

        $this->logStep('getPolicies', [
            'count'    => count($policies),
            'filters'  => $params,
        ]);

        return [
            'success'   => true,
            'policies'  => $policies,
            'count'     => count($policies),
            'html'      => $html,
            'log_dir'   => $this->options['log'] ? $this->logDir : null,
        ];
    }

    // ===================================================================
    // XATO
    // ===================================================================

    private function fail(string $step, string $error, ?array $payload = null): array
    {
        $result = [
            'success' => false,
            'error'   => $error,
            'step'    => $step,
            'payload' => $payload,
        ];
        $this->logStep('fail_' . $step, $result);

        if ($this->options['throw_on_error']) {
            throw new UzbekOsagoException($error, $step, $payload);
        }
        return $result;
    }

    // ===================================================================
    // LOGLASH
    // ===================================================================

    private function beginLog(): void
    {
        $this->runId = 'uz' . date('ymdHis') . bin2hex(random_bytes(4));

        if (!$this->options['log']) {
            $this->logDir = null;
            return;
        }
        $this->logDir = rtrim($this->options['log_root'], '/\\') . '/' . date('Y-m-d_H-i-s');
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
        $this->logSeq = 0;
    }

    private function logStep(string $name, mixed $data): void
    {
        $request = $this->client->pullLastRequest();
        $this->logToDatabase($name, $request, $data);

        if (!$this->options['log'] || !$this->logDir) {
            return;
        }
        $this->logSeq++;
        $file = $this->logDir . '/' . $this->logSeq . '_' . $name . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Qadamni admin paneldagi "Job logs" (gross_api_log, provider_id = Uzbekinvest) ga yozadi.
     * Yozish xatosi sug'urta oqimini to'xtatmasligi uchun yutiladi.
     */
    private function logToDatabase(string $name, ?string $request, mixed $data): void
    {
        if (!$this->options['db_log'] || !class_exists(\Yii::class, false) || \Yii::$app === null) {
            return;
        }

        try {
            $isFail = str_starts_with($name, 'fail_');
            $isErrorPayload = is_array($data) && (
                ($data['status'] ?? '') === 'error' || ($data['success'] ?? true) === false
            );
            $error = null;
            if (is_array($data)) {
                $error = $data['error'] ?? $data['message'] ?? null;
                if (!$isFail && !$isErrorPayload) {
                    $error = null;
                }
            }

            (new \common\models\GrossApiLog([
                'provider_id'   => \common\models\Police::PROVIDER_UZBEKINVEST,
                'chat_id'       => (int)$this->options['chat_id'] ?: null,
                'run_id'        => $this->runId,
                'label'         => mb_substr($name, 0, 64),
                'attempt'       => 1,
                'success'       => !$isFail && !$isErrorPayload,
                'request'       => $request,
                'response'      => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'error_message' => is_string($error) ? mb_substr($error, 0, 512) : null,
            ]))->save(false);
        } catch (\Throwable $e) {
            \Yii::warning('Uzbekinvest job logini yozishda xato: ' . $e->getMessage(), 'uzbekinvest');
        }
    }

    // ===================================================================
    // SUBMIT MAYDONLARINI YIG'ISH
    // ===================================================================

    private function normalizePassport(string $p): string
    {
        return preg_match('/^([A-Z]{2})(\d{7})$/', $p, $m) ? $m[1] . ' ' . $m[2] : $p;
    }

    /**
     * $ownerOrOrg: yuridik egada GET_ORG_BY_INN javobi, jismoniy egada
     * GET_PERSON_BY_BIRTH javobi.
     */
    private function buildSubmitFields(array $in, array $vehicle, ?array $ownerOrOrg, array $driverList, array $premiumResult): array
    {
        $isOrg = (int)($vehicle['owner_type'] ?? 0) === 1;
        $inn   = $vehicle['inn'] ?? $vehicle['tin'] ?? '';

        $applicantFields = $isOrg
            ? $this->buildApplicantFieldsOrg($ownerOrOrg, $inn, $in['phone'])
            : $this->buildApplicantFieldsPerson($ownerOrOrg, $in['owner_passport'], $in['owner_birthdate'], $in['phone']);

        $vehicleOwnerFields = $isOrg
            ? $this->buildVehicleOwnerFieldsOrg($vehicle, $ownerOrOrg, $inn)
            : $this->buildVehicleOwnerFieldsPerson($vehicle);

        $drFields = $this->buildDriverFields($driverList);

        return array_merge([
            // Mahsulot
            'P252_INSURANCE_PRODUCT_ID'  => '3',
            'P252_FIELDS_LOCK'           => '',
            'P252_FACE_ID'               => '',
            'P252_CONTRACT_ID'           => '',

            // Mashina (getVehicleInfo javobidan)
            'P252_IS_FOREIGN'            => $in['is_foreign'],
            'P252_GOV_NUMBER'            => $in['gov_number'],
            'P252_TECH_SERY'             => $in['tech_sery'],
            'P252_TECH_NUMBER'           => $in['tech_number'],
            'P252_TECH_DATE'             => $vehicle['tech_date']      ?? $in['tech_date'],
            'P252_VEHICLE_TYPE'          => $vehicle['vehicle_type']   ?? $in['vehicle_type'],
            'P252_VEHICLE_TYPE_HID'      => $vehicle['vehicle_type']   ?? $in['vehicle_type'],
            'P252_VEHICLE_MODEL'         => $vehicle['model_name']     ?? '',
            'P252_VEHICLE_YEAR'          => $vehicle['vehicle_year']   ?? '',
            'P252_VEHICLE_BODY_NUMBER'   => $vehicle['body_number']    ?? '',
            'P252_VEHICLE_ENGINE_NUMBER' => $vehicle['engine_number']  ?? '',
            'P252_VEHICLE_ERROR'         => '',
            'P252_VEH_FOND_ID'           => $vehicle['vehicle_id']     ?? '',
            'P252_VEH_COLOR'             => $vehicle['vehicle_color']  ?? '',
            'P252_VEH_DIVISION'          => $vehicle['division']       ?? '',
            'P252_VEH_FULLWEIGHT'        => $vehicle['full_weight']    ?? '',
            'P252_VEH_EMPTY_WEIGHT'      => $vehicle['empty_weight']   ?? '',
            'P252_VEH_FUEL_TYPE'         => $vehicle['fuel_type']      ?? '',
            'P252_VEH_SEATS'             => $vehicle['seats']          ?? '',
            'P252_VEH_ENGINE_POWER'      => $vehicle['engine_power']   ?? '',
            'P252_VEH_PINFL'             => $vehicle['pinfl']          ?? '',
            'P252_VEH_OWNER'             => $vehicle['owner_name']     ?? '',
            'P252_VEH_INN'               => $vehicle['inn']            ?? '',
        ], $applicantFields, $vehicleOwnerFields, [
            // Haydovchilar umumiy
            'P252_DR_COUNT_HID'          => '',
            'P252_POLICY_TYPE'           => $in['policy_type'],
            'P252_DR_COUNT'              => count($driverList),
            'P252_DR_LIMIT'              => count($driverList),
            'P252_OWNER_IS_DRIVER'       => '0',

            // Sug'urta shartlari
            'P252_USE_TERITORY_ID'       => $in['use_territory_id'],
            'P252_IS_ACCIDENT_COIFFICIENT'     => '0',
            'P252_IS_ACCIDENT_COIFFICIENT_HID' => '0',
            'P252_HAS_DISCOUNT'          => '0',
            'P252_IS_PESIONER'           => '0',
            'P252_PERIOD_S_DATE'         => $in['start_date'],
            'P252_PERIOD'                => $in['period'],

            // Qo'shimcha sug'urtalar (tarif ID javobda bo'lmasa default)
            'P252_EXTRACT_ACCIDENT'      => '0',
            'P252_ACCIDENT_TARIF_ID'     => $premiumResult['accident_tarif_id'] ?? '59',
            'P252_EXTRACT_GAS_BALLOON'   => '0',
            'P252_GAS_BALLOON_TARIF_ID'  => $premiumResult['gas_balloon_tarif_id'] ?? '1',
            'P252_EXTRACT_DSAGO'         => '0',
            'P252_DSAGO_TARIF_ID'        => $premiumResult['dsago_tarif_id'] ?? '131',

            // Eski polis
            'P252_OLD_SERY'              => '',
            'P252_OLD_NUMBER'            => '',
            'P252_OLD_GOVNUMBER'         => '',
            'P252_ERSP_SERY'             => '',
            'P252_ERSP_NUMBER'           => '',
            'P252_ERSP_GOVNUMBER'        => '',
            'P252_OLD_GOV_NUMBER'        => '',
            'P252_OLD_TECH_SERY'         => '',
            'P252_OLD_TECH_NUMBER'       => '',

            // PINFL orqali qidirish
            'P252_IS_BY_PINFL'           => '0',
            'P252_BY_PINFL'              => '',
            'P252_BY_PASPORT'            => '',
            'P252_BY_BIRTHDATE'          => '',
        ], $drFields);
    }

    private function buildDriverFields(array $driverList): array
    {
        // 10 ta slot — real so'rovga mos; P252_DR_COEFFICIENT_* TEN da yo'q
        $allSlots = ['FIRST', 'SECOND', 'THIRST', 'FORTY', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN'];
        $fields   = [];
        foreach ($allSlots as $i => $slot) {
            $d = $driverList[$i] ?? null;
            // Pasport formati: "AD 6970989" (bo'sh joy bilan) — real so'rov talabi
            $fields["P252_DR_PASPORT_{$slot}"]     = $d ? (($d['pas_sery'] ?? '') . ($d['pas_sery'] ? ' ' : '') . ($d['pas_number'] ?? '')) : '';
            $fields["P252_DR_BIRTHDATE_{$slot}"]   = $d['birthdate']       ?? '';
            $fields["P252_DR_PINFL_{$slot}"]       = $d['currentpinfl']    ?? '';
            $fields["P252_DR_RESIDENT_{$slot}"]    = '1';
            $fields["P252_DR_LASTNAME_{$slot}"]    = $d['lastnamelatin']   ?? '';
            $fields["P252_DR_FIRSTNAME_{$slot}"]   = $d['firstnamelatin']  ?? '';
            $fields["P252_DR_MIDDLENAME_{$slot}"]  = $d['middlenamelatin'] ?? '';
            $fields["P252_DR_GENDER_{$slot}"]      = $d ? (string)($d['gender'] ?? 1) : '1';
            $fields["P252_DR_ISSUE_BY_{$slot}"]    = $d['docgiveplace']    ?? '';
            $fields["P252_DR_ISSUE_DATE_{$slot}"]  = $d['datebegin']       ?? '';
            if ($slot !== 'TEN') {
                $fields["P252_DR_COEFFICIENT_{$slot}"] = $d['coefficient'] ?? ($d ? '1' : '');
            }
            $fields["P252_RELATIVE_{$slot}"]       = '0';
            $fields["P252_DR_SERY_{$slot}"]        = $d['license_seria']   ?? '';
            $fields["P252_DR_NUMBER_{$slot}"]      = $d['license_number']  ?? '';
            $fields["P252_DR_DATE_{$slot}"]        = $d['issue_date']      ?? '';
        }
        return $fields;
    }

    private function buildApplicantFieldsOrg(array $orgOwner, string $inn, string $phone): array
    {
        return [
            'P252_HIDE_IS_APPLICANT'         => '0',
            'P252_VEHICHLE_IS_APPLICANT'     => '1',
            'P252_APPLICANT_IS_ORGANIZATION' => '1',
            'P252_APPLICANT_PASPORT'         => '',
            'P252_APPLICANT_BIRTHDATE'       => '',
            'P252_APPLICANT_PINFL'           => '',
            'P252_APPLICANT_LASTNAME'        => '',
            'P252_APPLICANT_FIRSTNAME'       => '',
            'P252_APPLICANT_MIDDLENAME'      => '',
            'P252_APPLICANT_GENDER'          => '1',
            'P252_APPLICANT_ISSUE_BY'        => '',
            'P252_APPLICANT_ISSUE_DATE'      => '',
            'P252_APPLICANT_INN'             => $inn,
            'P252_APPLICANT_ORG_NAME'        => $orgOwner['org_name']   ?? '',
            'P252_APPLICANT_REG_NUM'         => $orgOwner['reg_num']    ?? '',
            'P252_APPLICANT_REG_DATE'        => $orgOwner['reg_date']   ?? '',
            'P252_APPLICANT_OKED'            => $orgOwner['oked']       ?? '',
            'P252_APPLICANT_OKED_TITLE'      => $orgOwner['oked_title'] ?? '',
            'P252_APPLICANT_ORG_SHORT'       => $orgOwner['name_short'] ?? '',
            'P252_APPLICANT_ORG_FUND'        => $orgOwner['fund']       ?? '',
            'P252_APPLICANT_ORG_DIR'         => $orgOwner['rep_name']   ?? '',
            'P252_APPLICANT_ORG_SOATO'       => $orgOwner['soato']      ?? '',
            'P252_APPLICANT_BANK_MFO'        => $orgOwner['bank_mfo']   ?? '',
            'P252_APPLICANT_BANK_NAME'       => $orgOwner['bank_name']  ?? '',
            'P252_APPLICANT_ACCOUNT'         => $orgOwner['account']    ?? '',
            'P252_APPLICANT_OKPO'            => $orgOwner['okpo']       ?? '',
            'P252_APPLICANT_RESIDENT'        => '1',
            'P252_APPLICANT_PHONE'           => $phone,
            'P252_APPLICANT_EMAIL'           => $orgOwner['email']      ?? '',
            'P252_APPLICANT_COUNTRY'         => (string)($orgOwner['country']  ?? '210'),
            'P252_APPLICANT_REGION_HID'      => '',
            'P252_APPLICANT_REGION'          => (string)($orgOwner['region']   ?? ''),
            'P252_APPLICANT_DISTRICT_HID'    => (string)($orgOwner['district'] ?? ''),
            'P252_APPLICANT_DISTRICT'        => (string)($orgOwner['district'] ?? ''),
            'P252_APPLICANT_ADDRESS_HID'     => '',
            'P252_APPLICANT_ADDRESS'         => $orgOwner['address']    ?? '',
        ];
    }

    private function buildApplicantFieldsPerson(array $owner, string $ownerPassport, string $ownerBirthDate, string $phone): array
    {
        return [
            'P252_HIDE_IS_APPLICANT'         => '',
            'P252_VEHICHLE_IS_APPLICANT'     => '1',
            'P252_APPLICANT_IS_ORGANIZATION' => '0',
            'P252_APPLICANT_PASPORT'         => $this->normalizePassport($ownerPassport),
            'P252_APPLICANT_BIRTHDATE'       => $ownerBirthDate,
            'P252_APPLICANT_PINFL'           => $owner['pinfl']         ?? '',
            'P252_APPLICANT_LASTNAME'        => $owner['lastname']      ?? '',
            'P252_APPLICANT_FIRSTNAME'       => $owner['firstname']     ?? '',
            'P252_APPLICANT_MIDDLENAME'      => $owner['middlename']    ?? '',
            'P252_APPLICANT_GENDER'          => $owner['gender']        ?? '1',
            'P252_APPLICANT_ISSUE_BY'        => $owner['issue_by']      ?? '',
            'P252_APPLICANT_ISSUE_DATE'      => $owner['issue_date']    ?? '',
            'P252_APPLICANT_INN'             => '',
            'P252_APPLICANT_ORG_NAME'        => '',
            'P252_APPLICANT_REG_NUM'         => '',
            'P252_APPLICANT_REG_DATE'        => '',
            'P252_APPLICANT_OKED'            => '',
            'P252_APPLICANT_OKED_TITLE'      => '',
            'P252_APPLICANT_ORG_SHORT'       => '',
            'P252_APPLICANT_ORG_FUND'        => '',
            'P252_APPLICANT_ORG_DIR'         => '',
            'P252_APPLICANT_ORG_SOATO'       => '',
            'P252_APPLICANT_BANK_MFO'        => '',
            'P252_APPLICANT_BANK_NAME'       => '',
            'P252_APPLICANT_ACCOUNT'         => '',
            'P252_APPLICANT_OKPO'            => '',
            'P252_APPLICANT_RESIDENT'        => '1',
            'P252_APPLICANT_PHONE'           => $phone,
            'P252_APPLICANT_EMAIL'           => $owner['email']         ?? '',
            'P252_APPLICANT_COUNTRY'         => $owner['country']       ?? '210',
            'P252_APPLICANT_REGION_HID'      => '',
            'P252_APPLICANT_REGION'          => $owner['region']        ?? '',
            'P252_APPLICANT_DISTRICT_HID'    => $owner['district']      ?? '',
            'P252_APPLICANT_DISTRICT'        => $owner['district']      ?? '',
            'P252_APPLICANT_ADDRESS_HID'     => '',
            'P252_APPLICANT_ADDRESS'         => $owner['address']       ?? '',
        ];
    }

    private function buildVehicleOwnerFieldsOrg(array $vehicle, array $orgOwner, string $inn): array
    {
        return [
            'P252_VEHICLE_OWNER_TIN'         => $vehicle['tin']        ?? $inn,
            'P252_IS_ORGANIZATION_HID'       => '',
            'P252_VEHICHLE_IS_ORGANIZATION'  => '1',
            'P252_VEHICLE_PASPORT'           => '',
            'P252_VEHICLE_BIRTHDATE'         => '',
            'P252_VEHICLE_PINFL'             => '',
            'P252_VEHICLE_LASTNAME'          => '',
            'P252_VEHICLE_FIRSTNAME'         => '',
            'P252_VEHICLE_MIDDLENAME'        => '',
            'P252_VEHICLE_GENDER'            => '1',
            'P252_VEHICLE_ISSUE_BY'          => '',
            'P252_VEHICLE_ISSUE_DATE'        => '',
            'P252_VEHICLE_INN'               => $inn,
            'P252_VEHICLE_ORGNAME'           => $orgOwner['org_name']  ?? '',
            'P252_VEHICLE_REG_NUM'           => $orgOwner['reg_num']   ?? '',
            'P252_VEHICLE_REG_DATE'          => $orgOwner['reg_date']  ?? '',
            'P252_VEHICLE_ADDRESS'           => $orgOwner['address']   ?? '',
            'P252_VEHICLE_PHONE'             => $orgOwner['phone']     ?? '',
            'P252_VEHICLE_REGION'            => (string)($orgOwner['region']   ?? ''),
            'P252_VEHICLE_DISTRICT'          => (string)($orgOwner['district'] ?? ''),
            'P252_VEHICLE_OKED'              => $orgOwner['oked']      ?? '',
            'P252_VEHICLE_OKED_TITLE'        => $orgOwner['oked_title'] ?? '',
            'P252_VEHICLE_ORG_SHORT'         => $orgOwner['name_short'] ?? '',
            'P252_VEHICLE_ORG_FUND'          => $orgOwner['fund']      ?? '',
            'P252_VEHICLE_ORG_DIR'           => $orgOwner['rep_name']  ?? '',
            'P252_VEHICLE_ORG_SOATO'         => $orgOwner['soato']     ?? '',
            'P252_VEHICLE_BANK_MFO'          => $orgOwner['bank_mfo']  ?? '',
            'P252_VEHICLE_BANK_NAME'         => $orgOwner['bank_name'] ?? '',
            'P252_VEHICLE_ACCOUNT'           => $orgOwner['account']   ?? '',
            'P252_VEHICLE_OKPO'              => $orgOwner['okpo']      ?? '',
        ];
    }

    private function buildVehicleOwnerFieldsPerson(array $vehicle): array
    {
        return [
            'P252_VEHICLE_OWNER_TIN'         => $vehicle['tin']        ?? '',
            'P252_IS_ORGANIZATION_HID'       => '',
            'P252_VEHICHLE_IS_ORGANIZATION'  => '0',
            'P252_VEHICLE_PASPORT'           => '',
            'P252_VEHICLE_BIRTHDATE'         => '',
            'P252_VEHICLE_PINFL'             => $vehicle['pinfl']      ?? '',
            'P252_VEHICLE_LASTNAME'          => '',
            'P252_VEHICLE_FIRSTNAME'         => '',
            'P252_VEHICLE_MIDDLENAME'        => '',
            'P252_VEHICLE_GENDER'            => '1',
            'P252_VEHICLE_ISSUE_BY'          => '',
            'P252_VEHICLE_ISSUE_DATE'        => '',
            'P252_VEHICLE_INN'               => '',
            'P252_VEHICLE_ORGNAME'           => '',
            'P252_VEHICLE_REG_NUM'           => '',
            'P252_VEHICLE_REG_DATE'          => '',
            'P252_VEHICLE_ADDRESS'           => '',
            'P252_VEHICLE_PHONE'             => '',
            'P252_VEHICLE_REGION'            => '',
            'P252_VEHICLE_DISTRICT'          => '',
            'P252_VEHICLE_OKED'              => '',
            'P252_VEHICLE_OKED_TITLE'        => '',
            'P252_VEHICLE_ORG_SHORT'         => '',
            'P252_VEHICLE_ORG_FUND'          => '',
            'P252_VEHICLE_ORG_DIR'           => '',
            'P252_VEHICLE_ORG_SOATO'         => '',
            'P252_VEHICLE_BANK_MFO'          => '',
            'P252_VEHICLE_BANK_NAME'         => '',
            'P252_VEHICLE_ACCOUNT'           => '',
            'P252_VEHICLE_OKPO'              => '',
        ];
    }

    // ===================================================================
    // PARSERLAR
    // ===================================================================

    private function parseContractId(string $redirectUrl): ?string
    {
        $query = parse_url(rawurldecode($redirectUrl), PHP_URL_QUERY);
        if ($query === null) {
            return null;
        }
        parse_str($query, $parts);
        return !empty($parts['p210_contract_id']) ? (string)$parts['p210_contract_id'] : null;
    }

    /**
     * Shartnoma sahifasidagi to'lov URL larini ajratib olish.
     */
    private function extractPayUrls(string $html): array
    {
        $map = [
            'click'  => 'P210_CLICK_PAY_URL',
            'payme'  => 'P210_PAYME_PAY_URL',
            'uzum'   => 'P210_UZUM_PAY_URL',
            'paynet' => 'P210_PAYNET_PAY_URL',
        ];
        $result = ['click' => null, 'payme' => null, 'uzum' => null, 'paynet' => null];
        foreach ($map as $key => $field) {
            if (preg_match('/<input[^>]+id="' . $field . '"[^>]+value="([^"]+)"/i', $html, $m)) {
                $result[$key] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }
        }
        return $result;
    }

    /**
     * polices.html dagi a-IRR-table dan polislar ro'yxatini ajratib olish.
     * Jadval topilmasa null qaytaradi.
     */
    private function parsePoliciesTable(string $html): ?array
    {
        if (!preg_match('/<table[^>]+class="a-IRR-table"[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            // Filtr hech narsa topmasa APEX jadval o'rniga "No data found." belgisini chiqaradi — bu xato emas.
            return str_contains($html, 'icon-irr-no-results') ? [] : null;
        }

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableMatch[1], $rows);

        $policies = [];
        foreach ($rows[1] as $row) {
            if (stripos($row, '<th') !== false) continue; // sarlavha qatorini o'tkazib yuborish

            // ID — headers="C157465904138981922" li <td> ichidagi raqam
            $id = '';
            if (preg_match('/<td[^>]+headers="C157465904138981922"[^>]*>\s*(\d+)\s*<\/td>/i', $row, $idMatch)) {
                $id = trim($idMatch[1]);
            }

            // ersp.e-osgo.uz PDF havolasi
            $pdfUrl = null;
            if (preg_match('/<a\b[^>]+href="(https?:\/\/ersp\.e-osgo\.uz[^"]+)"/i', $row, $m)) {
                $pdfUrl = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }

            // Shartnoma raqami — qator havolalaridagi p101/p208/p210_contract_id=...
            // (u jadvaldagi "ID" ustunidan, ya'ni polis ID'sidan farq qiladi)
            $contractId = null;
            if (preg_match('/p\d+_contract_id(?:=|%3D)(\d+)/i', $row, $cm)) {
                $contractId = $cm[1];
            }

            // Holat: "Тасдиқланган" (to'langan) / "Янги тасдиқланмаган"
            $status = null;
            if (preg_match('/<td[^>]+headers="C157465202287981915"[^>]*>(.*?)<\/td>/si', $row, $sm)) {
                $status = trim(html_entity_decode(strip_tags($sm[1]), ENT_QUOTES | ENT_HTML5));
            }

            if ($id !== '') {
                $policies[] = ['id' => $id, 'contract_id' => $contractId, 'status' => $status, 'pdf_url' => $pdfUrl];
            }
        }
        return $policies;
    }

    // ===================================================================
    // YORDAMCHI
    // ===================================================================

    private function toAbsolute(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        $parsed = parse_url($this->options['base_url']);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/' . ltrim($url, '/');
    }
}
