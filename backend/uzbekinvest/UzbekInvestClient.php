<?php

namespace backend\uzbekinvest;

use RuntimeException;

class UzbekInvestClient
{
    private string  $baseUrl;
    private string  $username;
    private string  $password;
    private string  $cookieFile;
    private string  $sessionFile;
    private array   $defaultHeaders;
    private bool    $verifySsl;
    public  ?string $debugDir = null;

    public function __construct(
        string $username,
        string $password,
        string $storageDir = __DIR__,
        string $baseUrl = 'https://sales-platform.uzbekinvest.uz',
        bool $verifySsl = true
    ) {
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }
        $this->verifySsl   = $verifySsl;
        $this->baseUrl     = rtrim($baseUrl, '/');
        $this->username    = $username;
        $this->password    = $password;
        $this->cookieFile  = $storageDir . '/cookies.txt';
        $this->sessionFile = $storageDir . '/session.txt';

        $this->defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',
            'Accept-Language: ru,en-US;q=0.9,en;q=0.8,uz;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
    }

    // -------------------------------------------------------
    // Aktiv session qaytaradi — mavjud bo'lsa cookie, yo'qsa login
    // -------------------------------------------------------
    public function getSession(): string
    {
        $existing = $this->checkExistingSession();
        if ($existing) {
            return $existing['session'];
        }

        return $this->login();
    }

    // -------------------------------------------------------
    // Transport ma'lumotlarini olish
    // -------------------------------------------------------
    public function getVehicleInfo(string $session, string $x01, string $x02, string $x03): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $salt     = $this->extractSalt($pageResp['body']);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=GET_VEHICLE_INFO',
            'x01'            => $x01,
            'x02'            => $x02,
            'x03'            => $x03,
            'p_json'         => json_encode(['salt' => $salt], JSON_UNESCAPED_SLASHES),
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Tashkilot ma'lumotlarini INN bo'yicha olish (yuridik shaxs)
    // $inn      : tashkilot INN raqami
    // $ownerType: vehicle-info javobidagi owner_type (1 = yuridik)
    // -------------------------------------------------------
    public function getOrgByInn(string $session, string $inn, string $ownerType = '1'): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $salt     = $this->extractSalt($pageResp['body']);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=GET_ORG_BY_INN',
            'x01'            => $inn,
            'x02'            => $ownerType,
            'p_json'         => json_encode(['salt' => $salt], JSON_UNESCAPED_SLASHES),
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Sahifadagi PLUGIN= tokenlarini topib qaytaradi
    // APEX har sahifada DA uchun dinamik token yaratadi.
    // Qaytaradi: ['PLUGIN=xxx', 'PLUGIN=yyy', ...]
    // -------------------------------------------------------
    public function extractPluginTokens(string $session): array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);

        $tokens = [];
        // URL-encoded: PLUGIN%3D... yoki PLUGIN%253D... (ikki marta encode)
        if (preg_match_all('/PLUGIN(?:%3D|%253D)([A-Za-z0-9_\-~.%\/+]{20,})/i', $pageResp['body'], $m)) {
            foreach ($m[1] as $encoded) {
                $tokens[] = 'PLUGIN=' . urldecode($encoded);
            }
        }
        // Raw: PLUGIN=... (JS yoki HTML atributlarda)
        if (preg_match_all('/\bPLUGIN=([A-Za-z0-9_\-~.\/+]{20,})/', $pageResp['body'], $m)) {
            foreach ($m[1] as $token) {
                $tokens[] = 'PLUGIN=' . $token;
            }
        }

        return array_unique(array_values($tokens));
    }

    // -------------------------------------------------------
    // Shaxs ma'lumotlarini tug'ilgan sana va pasport bo'yicha olish
    // -------------------------------------------------------
    public function getPersonByBirth(string $session, string $birthDate, string $passport, string $x03 = '1'): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $salt     = $this->extractSalt($pageResp['body']);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=GET_PERSON_BY_BIRTH',
            'x01'            => $birthDate,
            'x02'            => $passport,
            'x03'            => $x03,
            'p_json'         => json_encode(['salt' => $salt], JSON_UNESCAPED_SLASHES),
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Haydovchi ma'lumotlarini tug'ilgan sana va pasport bo'yicha olish
    // -------------------------------------------------------
    public function getDriverByBirth(string $session, string $birthDate, string $passport, string $x03 = '1'): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $salt     = $this->extractSalt($pageResp['body']);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=GET_DRIVER_BY_BIRTH',
            'x01'            => $birthDate,
            'x02'            => $passport,
            'x03'            => $x03,
            'p_json'         => json_encode(['salt' => $salt], JSON_UNESCAPED_SLASHES),
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // OSAGO mukofotini hisoblash
    // $params: P252_* maydonlar qiymatlari (kalit => qiymat)
    // -------------------------------------------------------
    public function calculateOsagoPremium(string $session, array $params): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);

        $itemsToSubmit = [];
        foreach ($params as $name => $value) {
            $itemsToSubmit[] = ['n' => $name, 'v' => (string) $value];
        }

        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'      => $itemsToSubmit,
                'protected'          => $form['protected'] ?? '',
                'rowVersion'         => '',
                'formRegionChecksums'=> [],
            ],
            'salt' => $form['salt'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=CALCULATE_OSAGO_PREMIUM',
            'p_json'         => $pJson,
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Haydovchi koeffitsientini hisoblash
    // $params: P252_DR_* va boshqa maydonlar (kalit => qiymat)
    // -------------------------------------------------------
    public function calculateDriverCoef(string $session, array $params): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);

        $itemsToSubmit = [];
        foreach ($params as $name => $value) {
            $itemsToSubmit[] = ['n' => $name, 'v' => (string) $value];
        }

        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'       => $itemsToSubmit,
                'protected'           => $form['protected'] ?? '',
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
            'salt' => $form['salt'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=CALCULATE_DRIVER_COEF',
            'p_json'         => $pJson,
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // PLUGIN so'rovi — pageItems null (sahifa yuklanganda ishga tushadigan DA)
    // $pluginRequest: sahifa HTML dan olinadigan "PLUGIN=..." qiymati
    // -------------------------------------------------------
    public function pluginInit(string $session, string $pluginRequest): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $salt     = $this->extractSalt($pageResp['body']);

        $pJson = json_encode(['pageItems' => null, 'salt' => $salt], JSON_UNESCAPED_SLASHES);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => $pluginRequest,
            'p_json'         => $pJson,
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // PLUGIN so'rovi — pageItems bilan (haydovchi limiti o'zgarganda)
    // $pluginRequest: sahifa HTML dan olinadigan "PLUGIN=..." qiymati
    // $params: P252_* maydonlar (kalit => qiymat)
    // -------------------------------------------------------
    public function pluginWithItems(string $session, string $pluginRequest, array $params): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);

        $itemsToSubmit = [];
        foreach ($params as $name => $value) {
            $itemsToSubmit[] = ['n' => $name, 'v' => (string) $value];
        }

        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'       => $itemsToSubmit,
                'protected'           => $form['protected'] ?? '',
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
            'salt' => $form['salt'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => $pluginRequest,
            'p_json'         => $pJson,
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Sug'urta muddati tugash sanasini hisoblash
    // $startDate : boshlanish sanasi (DD.MM.YYYY)
    // $period    : muddat kodi — 1=3 oy, 2=6 oy, 3=9 oy, 4=12 oy (1 yil)
    // -------------------------------------------------------
    public function calculatePeriodEndDate(string $session, string $startDate, string $period): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);

        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'       => [
                    ['n' => 'P252_PERIOD_S_DATE', 'v' => $startDate],
                    ['n' => 'P252_PERIOD',         'v' => $period],
                ],
                'protected'           => $form['protected'] ?? '',
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
            'salt' => $form['salt'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ajaxUrl  = $this->baseUrl . '/ords/wwv_flow.ajax?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData = [
            'p_flow_id'      => '101',
            'p_flow_step_id' => '252',
            'p_instance'     => $session,
            'p_debug'        => '',
            'p_request'      => 'APPLICATION_PROCESS=CALCULATE_PERIOD_END_DATE',
            'p_json'         => $pJson,
        ];

        $resp = $this->makeRequest($ajaxUrl, [], $postData, $pageUrl, true);
        return json_decode($resp['body'], true);
    }

    // -------------------------------------------------------
    // Portfel (polislar ro'yxati) qidirish va natija HTML ni qaytarish
    // $params: P100_* filtrlari (P100_INSURANCE_PRODUCT, P100_PERIOD, ...)
    // P100_FILIAL, P100_DEPARTMENT, P100_USER — sahifa HTML dan avtomatik olinadi (protected)
    // -------------------------------------------------------
    public function searchPolicies(string $session, array $params): ?string
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/portfel-info?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);

        $itemsToSubmit = [];
        foreach ($params as $name => $value) {
            $itemsToSubmit[] = ['n' => $name, 'v' => (string)$value];
        }

        // P100_FILIAL, P100_DEPARTMENT, P100_USER — protected, HTML dan CK bilan olinadi
        foreach ($this->parseProtectedItemsCk($pageResp['body']) as $pi) {
            $itemsToSubmit[] = $pi;
        }

        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'       => $itemsToSubmit,
                'protected'           => $form['protected'] ?? '',
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
            'salt' => $form['salt'] ?? '',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $acceptUrl = $this->baseUrl . '/ords/wwv_flow.accept?p_context=insurance-products-sales-platform/portfel-info/' . $session;
        $postData  = [
            'p_flow_id'            => $form['p_flow_id']      ?? '101',
            'p_flow_step_id'       => $form['p_flow_step_id'] ?? '100',
            'p_instance'           => $session,
            'p_debug'              => '',
            'p_request'            => 'SEARCH',
            'p_reload_on_submit'   => 'S',
            'p_page_submission_id' => $form['p_page_submission_id'] ?? '',
            'p_json'               => $pJson,
        ];

        $postResp = $this->makeRequest($acceptUrl, [], $postData, $pageUrl, true);

        // Redirect URL ni aniqlash
        $postJson    = json_decode($postResp['body'], true);
        $redirectUrl = null;
        if (!empty($postJson['redirectURL'])) {
            $redirectUrl = $this->toAbsolute($postJson['redirectURL']);
        } elseif (in_array($postResp['code'], [301, 302, 303, 307, 308]) && $postResp['location']) {
            $redirectUrl = $this->toAbsolute($postResp['location']);
        }

        $targetUrl   = $redirectUrl ?? $pageUrl;
        $resultsResp = $this->makeRequest($targetUrl, [], null, $pageUrl);
        if (in_array($resultsResp['code'], [301, 302, 303, 307, 308]) && $resultsResp['location']) {
            $resultsResp = $this->makeRequest($this->toAbsolute($resultsResp['location']), [], null, $targetUrl);
        }

        return $resultsResp['code'] === 200 ? $resultsResp['body'] : null;
    }

    // -------------------------------------------------------
    // Redirect URL ni ochib HTML qaytaradi (shartnoma sahifasi)
    // Redirect nisbiy bo'lishi mumkin — toAbsolute orqali to'liq URL ga aylantiriladi
    // -------------------------------------------------------
    public function fetchContractPage(string $session, string $redirectUrl): ?string
    {
        $url  = $this->toAbsolute($redirectUrl);
        $ref  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $resp = $this->makeRequest($url, [], null, $ref);

        if (in_array($resp['code'], [301, 302, 303, 307, 308]) && $resp['location']) {
            $resp = $this->makeRequest($this->toAbsolute($resp['location']), [], null, $url);
        }

        return $resp['code'] === 200 ? $resp['body'] : null;
    }

    // -------------------------------------------------------
    // OSAGO polisini saqlash (yakuniy submit)
    // wwv_flow.accept ga POST qiladi — p_page_submission_id sahifadan olinadi
    // $params: barcha P252_* maydonlar (kalit => qiymat)
    // Qaytaradi: JSON javob yoki redirect bo'lsa ['redirectURL' => '...']
    // -------------------------------------------------------
    public function submitOsago(string $session, array $params): ?array
    {
        $pageUrl  = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/e-polis-osago4?session=' . $session;
        $pageResp = $this->makeRequest($pageUrl);
        $form     = $this->parseApexForm($pageResp['body']);


        if ($this->debugDir) {
            file_put_contents($this->debugDir . '/submit_page.html', $pageResp['body']);
        }

        // Sahifa HTML da js-ignoreChange hidden inputlarning boshlang'ich qiymatlari bo'sh bo'ladi,
        // JavaScript ularni AJAX javobidan to'ldiradi. Shu sababli p_json log qilamiz.

        // Regular items
        $itemsToSubmit = [];
        foreach ($params as $name => $value) {
            $itemsToSubmit[] = ['n' => $name, 'v' => (string) $value];
        }

        // Protected display-only items — must include their per-item ck checksum
        // APEX stores: <input name="ITEM" value="VAL"> + <input data-for="ITEM" value="CK">
        $protectedItems = $this->parseProtectedItemsCk($pageResp['body']);
        foreach ($protectedItems as $pi) {
            $itemsToSubmit[] = $pi;
        }

        $salt      = $form['salt'] ?? '';
        $submId    = $form['p_page_submission_id'] ?? '';
        $protected = $form['protected'] ?? '';

        // pageItems first, salt last — matches browser order
        $pJson = json_encode([
            'pageItems' => [
                'itemsToSubmit'       => $itemsToSubmit,
                'protected'           => $protected,
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
            'salt' => $salt,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $acceptUrl = $this->baseUrl . '/ords/wwv_flow.accept?p_context=insurance-products-sales-platform/e-polis-osago4/' . $session;
        $postData  = [
            'p_flow_id'            => $form['p_flow_id']      ?? '101',
            'p_flow_step_id'       => $form['p_flow_step_id'] ?? '252',
            'p_instance'           => $form['p_instance']     ?? $session,
            'p_debug'              => '',
            'p_request'            => 'SAVE',
            'p_reload_on_submit'   => 'S',
            'p_page_submission_id' => $submId,
            'p_json'               => $pJson,
        ];

        if ($this->debugDir) {
            file_put_contents($this->debugDir . '/submit_pjson.json', $pJson);
        }


        $resp = $this->makeRequest($acceptUrl, [], $postData, $pageUrl, true);

        if ($this->debugDir) {
            file_put_contents($this->debugDir . '/submit_response.txt', $resp['headers'] . "\n\n" . $resp['body']);
        }

        $json = json_decode($resp['body'], true);
        if ($json !== null) {
            return $json;
        }

        // Redirect bo'lsa (302) — muvaffaqiyatli saqlanganlik belgisi
        if ($resp['location']) {
            return ['redirectURL' => $resp['location'], 'code' => $resp['code']];
        }

        return ['code' => $resp['code'], 'body' => $resp['body']];
    }

    // -------------------------------------------------------
    // Login — session ID qaytaradi
    // -------------------------------------------------------
    private function login(): string
    {
        // Sayt ba'zan login POST'ni "Сессия муддати тугади" bilan rad etadi — toza
        // sessiya bilan bir marta qayta uriniladi, boshqa xatolar darhol qaytariladi.
        try {
            return $this->doLogin();
        } catch (RuntimeException $e) {
            if (mb_stripos($e->getMessage(), 'Сессия') === false) {
                throw $e;
            }
            usleep(1500000);

            return $this->doLogin();
        }
    }

    /**
     * Cookie/session fayllari yoziladigan bo'lishi shart: aks holda curl cookie'ni
     * saqlay olmaydi va sayt "Сессия муддати тугади" deb javob beradi. Ko'pincha
     * sabab — papka/fayl boshqa foydalanuvchi (masalan root) nomidan yaratilgani.
     */
    private function assertStorageWritable(): void
    {
        $dir = dirname($this->cookieFile);
        foreach ([$this->cookieFile, $this->sessionFile] as $file) {
            if (is_file($file) && !is_writable($file)) {
                throw new RuntimeException("Uzbekinvest fayli yozib bo'lmaydi: {$file} (jarayon foydalanuvchisi: " . $this->processUser() . ')');
            }
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("Uzbekinvest papkasi yozib bo'lmaydi: {$dir} (jarayon foydalanuvchisi: " . $this->processUser() . ')');
        }
    }

    private function processUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = posix_getpwuid(posix_geteuid());

            return $info['name'] ?? (string)posix_geteuid();
        }

        return get_current_user();
    }

    private function doLogin(): string
    {
        $this->assertStorageWritable();

        // Eskirgan sessiya cookie'lari bilan login sahifasi ochilsa, sayt login POST'ni
        // "Сессия муддати тугади" bilan rad etadi — shuning uchun har doim toza sessiyadan boshlaymiz.
        foreach ([$this->cookieFile, $this->sessionFile] as $file) {
            if (is_file($file) && !@unlink($file)) {
                throw new RuntimeException("Uzbekinvest eski faylini o'chirib bo'lmadi: {$file} (jarayon foydalanuvchisi: " . $this->processUser() . ')');
            }
        }

        // 1. Bosh sahifa
        $step1 = $this->makeRequest($this->baseUrl . '/');
        if (in_array($step1['code'], [301, 302, 303, 307, 308]) && $step1['location']) {
            $step1 = $this->makeRequest($this->toAbsolute($step1['location']), [], null, $this->baseUrl . '/');
        }

        // 2. Login sahifasini olamiz
        $loginUrl = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/login';
        $step2    = $this->makeRequest($loginUrl, [], null, $this->baseUrl . '/');
        if (in_array($step2['code'], [301, 302, 303, 307, 308]) && $step2['location']) {
            $step2 = $this->makeRequest($this->toAbsolute($step2['location']), [], null, $loginUrl);
        }

        $form = $this->parseApexForm($step2['body']);
        if (!$form['p_instance']) {
            throw new RuntimeException('Login sahifasida session ID topilmadi');
        }

        // 3. Login POST
        $pJson = json_encode([
            'salt'      => $form['salt'] ?? '',
            'pageItems' => [
                'itemsToSubmit' => [
                    ['n' => 'P9999_USERNAME', 'v' => $this->username],
                    ['n' => 'P9999_PASSWORD', 'v' => $this->password],
                    ['n' => 'P9999_REMEMBER', 'v' => 'Y'],
                ],
                'protected'           => $form['protected'] ?? '',
                'rowVersion'          => '',
                'formRegionChecksums' => [],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $postData = [
            'p_flow_id'            => $form['p_flow_id']            ?? '101',
            'p_flow_step_id'       => $form['p_flow_step_id']       ?? '9999',
            'p_instance'           => $form['p_instance'],
            'p_debug'              => '',
            'p_request'            => 'LOGIN',
            'p_reload_on_submit'   => 'S',
            'p_page_submission_id' => $form['p_page_submission_id'] ?? '',
            'p_json'               => $pJson,
        ];

        $loginPostUrl = $this->baseUrl . '/ords/wwv_flow.accept?p_context=insurance-products-sales-platform/login/' . $form['p_instance'];
        $step3        = $this->makeRequest($loginPostUrl, [], $postData, $loginUrl, true);

        // 4. Natijani tekshirish
        $responseJson = json_decode($step3['body'], true);

        if (!empty($responseJson['error'])) {
            throw new RuntimeException('Login xatosi: ' . $responseJson['error']);
        }

        $redirectUrl = null;
        if (!empty($responseJson['redirectURL'])) {
            $redirectUrl = $this->toAbsolute($responseJson['redirectURL']);
        } elseif (in_array($step3['code'], [301, 302, 303]) && $step3['location']) {
            $redirectUrl = $this->toAbsolute($step3['location']);
        }

        if (!$redirectUrl) {
            throw new RuntimeException('Login muvaffaqiyatsiz — redirect yo\'q');
        }

        // 5. Dashboard ni ochib username ni aniqlaymiz
        $step4 = $this->makeRequest($redirectUrl, [], null, $this->baseUrl . '/');
        if (in_array($step4['code'], [301, 302, 303]) && $step4['location']) {
            $step4 = $this->makeRequest($this->toAbsolute($step4['location']), [], null, $redirectUrl);
        }

        $username = $this->parseUsername($step4['body']);

        // Session ID ni saqlaymiz
        file_put_contents($this->sessionFile, $form['p_instance']);

        return $form['p_instance'];
    }

    // -------------------------------------------------------
    // Mavjud cookie + session fayl bilan tekshirish
    // -------------------------------------------------------
    private function checkExistingSession(): ?array
    {
        if (!file_exists($this->cookieFile) || filesize($this->cookieFile) === 0) {
            return null;
        }

        $savedSession = file_exists($this->sessionFile) ? trim(file_get_contents($this->sessionFile)) : '';
        if (!$savedSession) {
            return null;
        }

        $dashUrl = $this->baseUrl . '/ords/r/uuis/insurance-products-sales-platform/%D1%8F%D0%BD%D0%B3%D0%B8-%D1%88%D0%B0%D1%80%D1%82%D0%BD%D0%BE%D0%BC%D0%B0?session=' . $savedSession;
        $resp    = $this->makeRequest($dashUrl);

        if (in_array($resp['code'], [301, 302, 303]) && $resp['location']) {
            if (stripos($resp['location'], 'login') !== false) {
                return null;
            }
            $resp = $this->makeRequest($this->toAbsolute($resp['location']), [], null, $dashUrl);
        }

        if ($resp['code'] !== 200) {
            return null;
        }

        $username = $this->parseUsername($resp['body']);
        if (!$username || strtolower($username) === 'nobody') {
            return null;
        }

        return ['session' => $savedSession, 'username' => $username];
    }

    // -------------------------------------------------------
    // HTTP so'rov
    // -------------------------------------------------------
    private function makeRequest(string $url, array $extraHeaders = [], ?array $postData = null, ?string $referer = null, bool $isAjax = false): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HEADER         => true,
        ]);

        $headers = $this->defaultHeaders;
        if ($referer) {
            $headers[] = 'Referer: ' . $referer;
        }

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $headers[] = 'Origin: ' . parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
            if ($isAjax) {
                $headers[] = 'X-Requested-With: XMLHttpRequest';
                $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
            } else {
                $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8';
                $headers[] = 'Upgrade-Insecure-Requests: 1';
            }
        } else {
            $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8';
            $headers[] = 'Upgrade-Insecure-Requests: 1';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, $extraHeaders));

        $raw        = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $finalUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error      = curl_error($ch);
        curl_close($ch);

        $respHeaders = substr($raw, 0, $headerSize);
        $body        = substr($raw, $headerSize);

        $location = null;
        if (preg_match('/^Location:\s*(.+)$/mi', $respHeaders, $lm)) {
            $location = trim($lm[1]);
        }

        return [
            'code'     => $httpCode,
            'headers'  => $respHeaders,
            'body'     => $body,
            'location' => $location,
            'finalUrl' => $finalUrl,
            'error'    => $error,
        ];
    }

    // -------------------------------------------------------
    // Protected item ck checksumlarini HTML dan olish
    // APEX sahifada: <input name="ITEM" value="VAL">
    //                <input data-for="ITEM" value="CK_HASH">
    // -------------------------------------------------------
    private function parseProtectedItemsCk(string $html): array
    {
        $ckMap = [];
        preg_match_all('/<input\b[^>]*>/i', $html, $allTags);
        foreach ($allTags[0] as $tag) {
            if (preg_match('/\bdata-for="([^"]+)"/i', $tag, $m) &&
                preg_match('/\bvalue="([^"]*)"/i', $tag, $v)) {
                $ckMap[$m[1]] = $v[1];
            }
        }

        $items = [];
        foreach ($ckMap as $name => $ck) {
            $value = '';
            if (preg_match('/<input\b[^>]*\bname="' . preg_quote($name, '/') . '"[^>]*>/i', $html, $vm)) {
                // Select/LOV itemlar uchun: data-return-value = APEX sessiyasidagi haqiqiy qiymat
                // (value attributi ko'rsatish uchun label bo'lishi mumkin)
                if (preg_match('/\bdata-return-value="([^"]*)"/i', $vm[0], $drv)) {
                    $value = html_entity_decode($drv[1], ENT_QUOTES | ENT_HTML5);
                } elseif (preg_match('/\bvalue="([^"]*)"/i', $vm[0], $va)) {
                    $value = html_entity_decode($va[1], ENT_QUOTES | ENT_HTML5);
                }
            }
            $items[] = ['n' => $name, 'v' => $value, 'ck' => $ck];
        }

        return $items;
    }

    // -------------------------------------------------------
    // APEX form tokenlarini parse qilish
    // -------------------------------------------------------
    private function parseApexForm(string $html): array
    {
        $result = [
            'p_instance'           => null,
            'p_flow_id'            => null,
            'p_flow_step_id'       => null,
            'p_page_submission_id' => null,
            'protected'            => null,
            'salt'                 => null,
        ];

        // Attribute tartibi muhim emas: <input> tegini topib value ni olamiz
        $inputVal = function (string $name) use ($html): ?string {
            if (preg_match('/<input[^>]+name=["\']' . preg_quote($name, '/') . '["\'][^>]*>/i', $html, $tag)) {
                if (preg_match('/\bvalue="([^"]*)"/i', $tag[0], $m)) {
                    return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
                }
            }
            return null;
        };

        $result['p_instance']           = $inputVal('p_instance');
        $result['p_flow_id']            = $inputVal('p_flow_id');
        $result['p_flow_step_id']       = $inputVal('p_flow_step_id');
        $result['p_page_submission_id'] = $inputVal('p_page_submission_id');

        // protected: id=pPageItemsProtected
        if (preg_match('/<input[^>]+id=["\']pPageItemsProtected["\'][^>]*>/i', $html, $tag)) {
            if (preg_match('/\bvalue="([^"]*)"/i', $tag[0], $m)) {
                $result['protected'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }
        }

        $result['salt'] = $this->extractSalt($html);

        // Fallback: p_json hidden input dan olish (yangi APEX formatlari)
        if (!$result['protected'] || !$result['p_page_submission_id'] || !$result['salt']) {
            if (preg_match('/<input[^>]+name=["\']p_json["\'][^>]*>/i', $html, $tag)) {
                if (preg_match('/\bvalue="([^"]+)"/i', $tag[0], $m)) {
                    $json = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
                    if ($json) {
                        $result['protected']            = $result['protected']            ?? ($json['pageItems']['protected'] ?? null);
                        $result['salt']                 = $result['salt']                 ?? ($json['salt']             ?? null);
                        $result['p_page_submission_id'] = $result['p_page_submission_id'] ?? ($json['pageSubmissionId'] ?? null);
                    }
                }
            }
        }

        if (!$result['p_instance'] && preg_match('/[?&]session=(\d+)/', $html, $m)) {
            $result['p_instance'] = $m[1];
        }

        return $result;
    }

    // -------------------------------------------------------
    // pSalt qiymatini HTML dan olish
    // -------------------------------------------------------
    private function extractSalt(string $html): string
    {
        if (preg_match('/<input[^>]+id=["\']pSalt["\'][^>]*>/i', $html, $tag)) {
            if (preg_match('/value="([^"]+)"/', $tag[0], $m)) {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }
        }
        return '';
    }

    // -------------------------------------------------------
    // Login bo'lgan username ni HTML dan aniqlash
    // -------------------------------------------------------
    private function parseUsername(string $html): ?string
    {
        if (preg_match('/"APP_USER"\s*:\s*"([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/APP_USER\s*:\s*"([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<span[^>]+class="[^"]*t-Header-userID[^"]*"[^>]*>\s*([^<]+)\s*<\/span>/i', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // -------------------------------------------------------
    // Relative URL ni absolute ga aylantirish
    // -------------------------------------------------------
    private function toAbsolute(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        $parsed = parse_url($this->baseUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/' . ltrim($url, '/');
    }
}