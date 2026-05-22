<?php


namespace backend\gross;
use RuntimeException;

class GrossOsago
{
    private GrossOsagoClient $http;
    private string $login;
    private string $password;
    private string $senderPinfl;
    private int    $markaId;
    private string $openaiApiKey;
    private string $responseDir;

    public function __construct(array $config)
    {
        $this->http         = new GrossOsagoClient();
        $this->login        = $config['login'];
        $this->password     = $config['password'];
        $this->senderPinfl  = $config['sender_pinfl'];
        $this->markaId      = (int) ($config['marka_id'] ?? 13);
        $this->openaiApiKey = $config['openai_key'];
        $this->responseDir  = $config['response_dir'] ?? __DIR__ . '/responses';
    }

    // ================================================================
    // PUBLIC
    // ================================================================

    /**
     * @return array{uuid: string, anketa_id: string, premium: int, click_url: ?string, payme_url: ?string, session_dir: string}
     */
    public function run(array $policyData): array
    {
        $sessionDir = $this->makeSessionDir();

        $this->ensureLoggedIn();

        $seria     = strtoupper($policyData['vehicle']['seria']);
        $number    = strtoupper($policyData['vehicle']['number']);
        $govNumber = strtoupper($policyData['vehicle']['gov_number']);

        $vehicle = $this->call('vehicle',
            fn() => $this->http->getVehicle($seria, $number, $govNumber),
            $sessionDir
        );
        if (!$vehicle) throw new RuntimeException("Vehicle ma'lumoti olinmadi");
        $vehicleResult = $vehicle['result'];


        $isOrg = (bool) $policyData['owner']['is_org'];

        if ($isOrg) {
            $ownerSection = $this->resolveOrg($vehicleResult, $sessionDir);
            $ownerPinfl   = null;
        } else {
            [$ownerSection, $ownerPinfl] = $this->resolvePerson(
                $vehicleResult,
                strtoupper($policyData['owner']['passport'] ?? ''),
                $sessionDir
            );
        }

        $drivers = [];
        if ($policyData['policy_type'] === 'limited') {
            $drivers = $this->resolveDrivers($policyData['drivers'] ?? [], $sessionDir);
        }

        $phone = $policyData['phone'];
        $this->call('phone-checker',
            fn() => $this->http->phoneNumberChecker($phone, 'osago'),
            $sessionDir
        );

        $kbm = $isOrg ? 1.0 : $this->resolveKbm($ownerPinfl, $sessionDir);


        $contractData = $this->buildContract($policyData, $vehicleResult, $ownerSection, $drivers, $phone, $kbm);


        print_r($contractData);


//        $contractResp = $this->call('contract',
//            fn() => $this->http->createContract($contractData),
//            $sessionDir
//        );
//
//
//        print_r($contractResp);
//
//
//        if (!$contractResp) throw new RuntimeException("Shartnoma yaratilmadi");
//
//        $uuid     = $contractResp['uuid']       ?? ($contractResp['data']['uuid'] ?? null);
//        $anketaId = (string) ($contractResp['anketa_id'] ?? '');
//        $premium  = (int) ($contractResp['premium'] ?? 0);



        $uuid     = 'b50f1184-32dd-40dc-9020-60ed187cb540';
        $anketaId = '5647130';
        $premium  = '56000';


        $clickHtml = $this->http->payWithClick($uuid, $anketaId);
        file_put_contents($sessionDir . '/payment-click.html', $clickHtml);

        $paymeHtml = $this->http->payWithPayme($uuid, $anketaId);
        file_put_contents($sessionDir . '/payment-payme.html', $paymeHtml);

        return [
            'uuid'        => $uuid,
            'anketa_id'   => $anketaId,
            'premium'     => $premium,
            'click_url'   => $this->http->extractPaymentLink($clickHtml),
            'payme_url'   => $this->http->extractPaymentLink($paymeHtml),
            'session_dir' => $sessionDir,
        ];
    }

    // ================================================================
    // PRIVATE — SESSION / LOGIN
    // ================================================================

    private function makeSessionDir(): string
    {
        $dir = $this->responseDir . '/' . date('Y-m-d_H-i-s');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private function ensureLoggedIn(int $maxAttempts = 3): void
    {
        if ($this->isSessionAlive()) {
            return;
        }

        $this->clearSessionCookie();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $html = $this->http->openHome();

            $captchaSrc = $this->http->getCaptchaSrc($html);
            if (!$captchaSrc) throw new RuntimeException("Captcha topilmadi");

            $captchaFile = $this->http->loadCaptcha($captchaSrc);
            $captchaCode = $this->http->solveCaptchaWithOpenAI($captchaFile, $this->openaiApiKey);

            $loginResponse = $this->http->login($this->login, $this->password, $captchaCode);

            if (stripos($loginResponse, 'name="UserName"') === false) {
                return;
            }

            if ($attempt < $maxAttempts) {
                sleep(2);
            }
        }

        throw new RuntimeException("Login muvaffaqiyatsiz ({$maxAttempts} ta urinishdan so'ng)");
    }

    private function isSessionAlive(): bool
    {
        try {
            $html = $this->http->dashboard();
            return stripos($html, 'name="UserName"') === false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function clearSessionCookie(): void
    {
        $cookieFile = __DIR__ . '/gross_cookie.txt';
        if (file_exists($cookieFile)) {
            unlink($cookieFile);
        }
    }

    // ================================================================
    // PRIVATE — API CALL
    // ================================================================

    private function call(string $label, callable $fn, string $sessionDir, int $tries = 3): ?array
    {
        for ($i = 1; $i <= $tries; $i++) {
            if ($i > 1) sleep(1);
            $json = $fn();
            $this->save($label, $json, $i, $sessionDir);
            $data = json_decode($json, true);
            if (($data['error'] ?? -1) === 0) return $data;
        }
        return null;
    }

    private function save(string $label, string $json, int $attempt, string $dir): void
    {
        $suffix = $attempt > 1 ? "_attempt{$attempt}" : '';
        $pretty = json_encode(
            json_decode($json, true),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        file_put_contents("{$dir}/{$label}{$suffix}.json", $pretty !== false ? $pretty : $json);
    }

    // ================================================================
    // PRIVATE — OWNER
    // ================================================================

    private function resolveOrg(array $vehicleResult, string $sessionDir): array
    {
        $inn = $vehicleResult['inn'] ?? null;
        if (!$inn) throw new RuntimeException("Vehicle javobida INN topilmadi");

        $resp = $this->call('company', fn() => $this->http->getCompanyByInn($inn), $sessionDir);
        if (!$resp) throw new RuntimeException("Tashkilot ma'lumoti olinmadi");

        $company = $resp['result'] ?? [];

        return [
            "legal_type"   => 1,
            "person"       => $this->emptyPerson(),
            "organization" => [
                "inn"            => $inn,
                "name"           => $company['name']    ?? null,
                "address"        => $company['address'] ?? null,
                "is_data_loaded" => true,
            ],
        ];
    }

    private function resolvePerson(array $vehicleResult, string $passport, string $sessionDir): array
    {
        $pinfl = $vehicleResult['pinfl'] ?? '';

        preg_match('/^([A-Za-z]+)(\d+)$/', $passport, $m);
        $series = $m[1] ?? '';
        $num    = $m[2] ?? '';

        $ownerResp = $this->call('owner', fn() => $this->http->getOwner(
            $passport, $pinfl, $this->senderPinfl, (string) round(microtime(true) * 1000)
        ), $sessionDir);
        if (!$ownerResp) throw new RuntimeException("Owner ma'lumoti olinmadi");

        $data = $ownerResp['result'] ?? [];
        $docs = $data['documents'] ?? [];
        $doc  = current(array_filter($docs, fn($d) => $d['document'] === $passport)) ?: ($docs[0] ?? []);

        $this->call('pensioner',
            fn() => $this->http->getIsPensioner($num, $series, $pinfl),
            $sessionDir
        );

        $ownerSection = [
            "legal_type"   => 0,
            "person"       => [
                "use_pinfl"            => true,
                "pinfl"                => $pinfl,
                "birthdate"            => $data['birthDate']      ?? null,
                "passport_series"      => $series,
                "passport_number"      => $num,
                "surname"              => $data['lastNameLatin']   ?? null,
                "firstname"            => $data['firstNameLatin']  ?? null,
                "patronym"             => $data['middleNameLatin'] ?? null,
                "passport_given_place" => $doc['docgiveplace']    ?? null,
                "passport_given_date"  => $doc['datebegin']       ?? null,
                "region_id"            => $data['regionId']       ?? null,
                "district_id"          => $data['districtId']     ?? null,
                "address"              => $data['address']        ?? null,
                "is_data_loaded"       => true,
            ],
            "organization" => ["inn" => null, "name" => null, "address" => null, "is_data_loaded" => false],
        ];

        return [$ownerSection, $pinfl];
    }

    // ================================================================
    // PRIVATE — DRIVERS
    // ================================================================

    private function resolveDrivers(array $driversInput, string $sessionDir): array
    {
        $drivers = [];

        foreach ($driversInput as $idx => $input) {
            $n   = $idx + 1;
            $doc = strtoupper($input['document']);

            $passportResp = $this->call("passport-{$n}", fn() => $this->http->getPassportByBirthDate(
                $doc, $input['birth_date'], $this->senderPinfl, (string) round(microtime(true) * 1000)
            ), $sessionDir);
            if (!$passportResp) throw new RuntimeException("#{$n} haydovchi passport olinmadi");

            $pinfl = $passportResp['result']['currentPinfl'] ?? '';

            $summaryResp = $this->call("driver-summary-{$n}", fn() => $this->http->getDriverSummary(
                $doc, $pinfl, $this->senderPinfl, (string) round(microtime(true) * 1000)
            ), $sessionDir);
            if (!$summaryResp) throw new RuntimeException("#{$n} haydovchi summary olinmadi");

            $s      = $summaryResp['result'] ?? [];
            $docs   = $s['DriverPersonInfo']['documents'] ?? [];
            $active = current(array_filter($docs, fn($d) => $d['document'] === $doc)) ?: ($docs[0] ?? []);

            $drivers[] = [
                "use_pinfl"            => false,
                "is_data_loaded"       => true,
                "pinfl"                => $pinfl,
                "passport_series"      => substr($doc, 0, 2),
                "passport_number"      => substr($doc, 2),
                "surname"              => $s['DriverPersonInfo']['lastNameLatin']   ?? null,
                "firstname"            => $s['DriverPersonInfo']['firstNameLatin']  ?? null,
                "patronym"             => $s['DriverPersonInfo']['middleNameLatin'] ?? null,
                "birthdate"            => $s['DriverPersonInfo']['birthDate']       ?? null,
                "license_sery"         => $s['DriverInfo']['licenseSeria']          ?? null,
                "license_number"       => $s['DriverInfo']['licenseNumber']         ?? null,
                "license_date"         => substr($s['DriverInfo']['issueDate'] ?? '', 0, 10),
                "passport_given_place" => $active['docgiveplace'] ?? null,
                "passport_given_date"  => $active['datebegin']    ?? null,
                "relative_type"        => (int) ($input['relative_type'] ?? 0),
                "uniqid"               => (string) round(microtime(true) * 1000),
            ];
        }

        return $drivers;
    }

    // ================================================================
    // PRIVATE — KBM / CONTRACT
    // ================================================================

    private function resolveKbm(string $pinfl, string $sessionDir): float
    {
        $resp = $this->call('coefficient',
            fn() => $this->http->getDriverCoefficient($pinfl),
            $sessionDir
        );
        return (float) ($resp['result']['coefficient'] ?? 1);
    }

    private function buildContract(
        array  $policyData,
        array  $vehicleResult,
        array  $ownerSection,
        array  $drivers,
        string $phone,
        float  $kbm
    ): array {
        $isOrg      = (bool) $policyData['owner']['is_org'];
        $periodType = (int) $policyData['period_type'];
        $seria      = strtoupper($policyData['vehicle']['seria']);
        $number     = strtoupper($policyData['vehicle']['number']);
        $prefix     = substr($vehicleResult['govNumber'], 0, 2);

        $startDate   = $policyData['start_date'];
        $endDate = match ($periodType) {
            1 => date('Y-m-d', strtotime($startDate . ' +6 months -1 day')),
            8 => date('Y-m-d', strtotime($startDate . ' +20 days -1 day')),
            default => date('Y-m-d', strtotime($startDate . ' +1 year -1 day')),
        };

        $applicant = array_merge($ownerSection, ['phone' => $phone]);
        if ($isOrg) $applicant['citizenship_id'] = null;

        return [
            "kbm"                => $kbm,
            "user_id"            => "9401",
            "policy_type"        => $policyData['policy_type'],
            "applicant_is_owner" => true,
            "vehicle" => [
                "gov_number"               => $vehicleResult['govNumber'],
                "tech_sery"                => $seria,
                "tech_number"              => $number,
                "gai_model"                => $vehicleResult['modelName'],
                "vehicle_type_id"          => $vehicleResult['vehicleTypeId'],
                "original_vehicle_type_id" => $vehicleResult['vehicleTypeId'],
                "disabled_vehicle_type"    => true,
                "production_year"          => $vehicleResult['issueYear'],
                "tech_issue_date"          => substr($vehicleResult['techPassportIssueDate'], 0, 10),
                "vin"                      => $vehicleResult['bodyNumber'],
                "engine"                   => $vehicleResult['engineNumber'],
                "use_territory_id"         => $this->useTerritory($prefix),
                "region_id"                => $this->region($prefix),
                "country_id"               => 182,
                "seats"                    => $vehicleResult['seats'],
                "is_data_loaded"           => true,
                "marka_id"                 => $this->markaId,
            ],
            "owner"     => $ownerSection,
            "applicant" => $applicant,
            "drivers"   => $drivers,
            "period"    => [
                "period_type" => $periodType,
                "start_date"  => $startDate,
                "end_date"    => $endDate,
            ],
            "is_discountable"                     => false,
            "discount_type"                       => 1,
            "is_applicant_person_api_not_working" => false,
            "is_owner_person_api_not_working"     => false,
            "reissue"                             => ["is_reissue" => false, "reissue_policy" => null],
            "is_vehicle_api_not_working"          => false,
        ];
    }

    // ================================================================
    // PRIVATE — HELPERS
    // ================================================================

    private function emptyPerson(): array
    {
        return [
            "use_pinfl"            => true,
            "pinfl"                => null,
            "birthdate"            => null,
            "passport_series"      => null,
            "passport_number"      => null,
            "surname"              => null,
            "firstname"            => null,
            "patronym"             => null,
            "passport_given_place" => null,
            "passport_given_date"  => null,
            "region_id"            => null,
            "district_id"          => null,
            "is_data_loaded"       => false,
        ];
    }

    private function useTerritory(string $prefix): int
    {
        $n = (int) $prefix;
        if ($n < 10)  return 1;
        if ($n < 20)  return 2;
        if ($n < 25)  return 34;
        if ($n < 30)  return 33;
        if ($n < 40)  return 10;
        if ($n < 50)  return 7;
        if ($n < 60)  return 8;
        if ($n < 70)  return 9;
        if ($n < 75)  return 13;
        if ($n < 80)  return 14;
        if ($n < 85)  return 11;
        if ($n < 90)  return 12;
        if ($n < 95)  return 32;
        if ($n < 100) return 31;
        return 1;
    }

    private function region(string $prefix): int
    {
        $n = (int) $prefix;
        if ($n < 10)  return 10;
        if ($n < 20)  return 11;
        if ($n < 25)  return 12;
        if ($n < 30)  return 13;
        if ($n < 40)  return 14;
        if ($n < 50)  return 15;
        if ($n < 60)  return 16;
        if ($n < 70)  return 17;
        if ($n < 75)  return 18;
        if ($n < 80)  return 19;
        if ($n < 85)  return 20;
        if ($n < 90)  return 21;
        if ($n < 95)  return 22;
        if ($n < 100) return 23;
        return 10;
    }
}
