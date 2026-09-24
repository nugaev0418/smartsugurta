<?php

namespace backend\ersp;

use DOMDocument;
use DOMXPath;
use Exception;

/**
 * ersp.e-osgo.uz (davlat OSAGO reestri) saytidan avtomobilning sug'urta
 * polisalari tarixini olish uchun screen-scraping klienti — backend/gross/
 * ostidagi GrossOsagoClient bilan bir xil naqsh: cookie-jar asosidagi
 * sessiya, sahifadan _csrf o'qish, captcha rasmini yuklab olish.
 */
class ErspVehicleClient
{
    private string $baseUrl = 'https://ersp.e-osgo.uz';
    private string $cookieDir;
    private string $captchaDir;
    private string $cookieFile;
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36';

    public function __construct(?string $cookieFile = null)
    {
        $this->cookieDir  = $this->resolveRuntimeDir('cookie');
        $this->captchaDir = $this->resolveRuntimeDir('captcha');

        if (!is_dir($this->cookieDir)) {
            mkdir($this->cookieDir, 0777, true);
        }

        if (!is_dir($this->captchaDir)) {
            mkdir($this->captchaDir, 0777, true);
        }

        // Har bir klient bitta sessiya (PHPSESSID) davomida ishlaydi —
        // shu sessiyaga tegishli cookie fayli boshidanoq unique nom bilan
        // yaratiladi, boshqa parallel so'rovlar/urinishlar bilan
        // aralashib ketmasligi uchun.
        $this->cookieFile = $cookieFile ?? $this->cookieDir . '/' . uniqid('ersp_cookie_', true) . '.txt';

        if (!file_exists($this->cookieFile)) {
            file_put_contents($this->cookieFile, '');
        }
    }

    ////////////////////////////////////////////////////////
    // COOKIE/CAPTCHA FAYLLARI QAYERGA YOZILADI
    //
    // Production'da `backend/ersp/` ilova kodi bilan birga deploy qilinadi
    // va odatda faqat deploy foydalanuvchisiga yozish huquqi beriladi — veb-
    // server (php-fpm) foydalanuvchisi u yerga mkdir/yoza olmaydi. Shuning
    // uchun Yii ilovasi ichida ishlaganda (BotController/WebAppController/
    // queue job — bularning barchasi shunday) har doim yozish huquqi
    // bo'ladigan `@runtime` (backend/runtime/) ostiga yoziladi. Yii
    // bootstrap qilinmagan holatda (masalan qo'lda `php -r`/test skripti
    // orqali sinovda) esa __DIR__ga qaytadi.
    ////////////////////////////////////////////////////////
    private function resolveRuntimeDir(string $sub): string
    {
        if (class_exists(\Yii::class) && \Yii::$app !== null) {
            return \Yii::getAlias("@runtime/ersp/{$sub}");
        }

        return __DIR__ . '/' . $sub;
    }

    ////////////////////////////////////////////////////////
    // CURL REQUEST
    ////////////////////////////////////////////////////////
    private function request(
        string $url,
        string $method = 'GET',
        array $headers = [],
        ?string $postData = null
    ): string {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception($error);
        }

        curl_close($curl);

        return $response;
    }

    ////////////////////////////////////////////////////////
    // 1. SESSIYANI OCHISH
    // /osago/index'ni ochadi — javob PHPSESSID va _csrf cookielarini
    // (cookie-jar fayliga) o'rnatadi va sahifa HTML'ida yangi _csrf
    // hidden-input qiymatini qaytaradi.
    ////////////////////////////////////////////////////////
    public function openIndex(): string
    {
        return $this->request(
            $this->baseUrl . '/osago/index',
            'GET',
            $this->htmlHeaders()
        );
    }

    ////////////////////////////////////////////////////////
    // HTML ICHIDAN _csrf QIYMATINI O'QISH
    //
    // MUHIM: cookie'dagi _csrf qiymati (maskalangan hash) va POST body'ga
    // qo'yiladigan _csrf qiymati Yii2'da BIR XIL EMAS — cookie doimiy
    // "unmask" qilingan tokenni saqlaydi, har bir sahifa render'ida esa
    // shu tokenga tasodifiy mask qo'shilib, YANGI qiymat HTML ichiga
    // (hidden input yoki <meta name="csrf-token">) chiqariladi. Shuning
    // uchun POST uchun kerakli _csrf qiymatini har doim shu HTML'dan
    // (openIndex() javobidan) o'qish kerak, cookie'dan emas.
    ////////////////////////////////////////////////////////
    public function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/name="_csrf(?:-\w+)?"\s+value="([^"]+)"/i', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/i', $html, $match)) {
            return $match[1];
        }

        return null;
    }

    ////////////////////////////////////////////////////////
    // 2. CAPTCHA RASMINI YUKLASH
    //
    // `v` PARAMETRI HAQIDA: bu Yii2'ning standart yii\captcha\Captcha
    // widget'i chiqaradigan cache-busting qiymati:
    //  - sahifa birinchi marta render qilinganda server PHP tomonda
    //    uniqid() bilan generatsiya qiladi (Captcha::getImageUrl()),
    //  - "Yangilash" tugmasi bosilganda esa brauzerdagi yii.captcha.js
    //    uni joriy timestamp (Date.now()) bilan almashtiradi.
    // Ikkala holatda ham bu qiymat SERVERDA CAPTCHA KODINI TEKSHIRISHDA
    // ishlatilmaydi — u faqat brauzer/kesh rasm-URL'ni qayta yuklab
    // olishi uchun kerak. Shuning uchun bu yerda o'zimiz istalgan
    // tasodifiy qiymat (masalan uniqid()) generatsiya qilsak bo'laveradi,
    // asosiysi — captcha so'rovi xuddi shu cookie-sessiya (PHPSESSID)
    // ustida yuborilishi kerak, chunki kod session'ga bog'langan holda
    // saqlanadi.
    ////////////////////////////////////////////////////////
    public function loadCaptcha(?string $v = null): string
    {
        $v = $v ?? uniqid('', true);

        $img = $this->request(
            $this->baseUrl . '/uz/site/captcha?v=' . $v,
            'GET',
            [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: ru,en-US;q=0.9,en;q=0.8,uz;q=0.7',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Referer: ' . $this->baseUrl . '/osago/index',
                'Sec-Fetch-Dest: image',
                'Sec-Fetch-Mode: no-cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: ' . $this->userAgent,
            ]
        );

        if (!$img || strlen($img) < 100) {
            throw new Exception('Captcha rasm yuklanmadi');
        }

        $file = $this->captchaDir . '/' . uniqid('ersp_captcha_', true) . '.jpg';
        file_put_contents($file, $img);
        clearstatcache();

        return $file;
    }

    ////////////////////////////////////////////////////////
    // CAPTCHA RASMINI OPENAI (GPT-4o VISION) BILAN O'QISH
    ////////////////////////////////////////////////////////
    public function solveCaptchaWithOpenAI(string $imagePath, string $openaiApiKey): string
    {
        $imageData = base64_encode(file_get_contents($imagePath));

        $payload = [
            'model'      => 'gpt-4o',
            'max_tokens' => 10,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url' => "data:image/jpeg;base64,{$imageData}",
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'This is a captcha image. Reply with ONLY the digits or characters you see, nothing else.',
                        ],
                    ],
                ],
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $openaiApiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            $errNo  = curl_errno($curl);
            $errMsg = curl_error($curl);
            curl_close($curl);
            throw new Exception("OpenAI so'rov xatosi [{$errNo}]: {$errMsg}");
        }
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception('OpenAI API xatosi: ' . ($data['error']['message'] ?? $response));
        }

        $text = trim($data['choices'][0]['message']['content'] ?? '');

        if ($text === '') {
            throw new Exception("OpenAI captcha javobini aniqlay olmadi. Javob: {$response}");
        }

        return $text;
    }

    ////////////////////////////////////////////////////////
    // 3. TEXPASPORT + DAVLAT RAQAMI BO'YICHA SO'ROV
    // Javob HTML'ida shu avtomobilga tegishli polisalar ro'yxati
    // bo'lishi kerak (extractPolicies() bilan ajratib olinadi).
    ////////////////////////////////////////////////////////
    public function submitVehicleForm(
        string $csrfToken,
        string $tpSeria,
        string $tpNumber,
        string $govNumber,
        string $verifyCode
    ): string {
        $data = http_build_query([
            '_csrf'                   => $csrfToken,
            'VehicleForm[tpSeria]'    => $tpSeria,
            'VehicleForm[tpNumber]'   => $tpNumber,
            'VehicleForm[govNumber]'  => $govNumber,
            'VehicleForm[verifyCode]' => $verifyCode,
        ]);

        return $this->request(
            $this->baseUrl . '/osago/index',
            'POST',
            array_merge($this->htmlHeaders(), [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: ' . $this->baseUrl,
                'Referer: ' . $this->baseUrl . '/osago/index',
            ]),
            $data
        );
    }

    ////////////////////////////////////////////////////////
    // TO'LIQ OQIM: SESSIYA -> CAPTCHA -> AI BILAN YECHISH -> SO'ROV
    //
    // Captcha noto'g'ri aniqlansa har bir urinishda YANGI sessiya/csrf/
    // captcha bilan qaytadan urinadi (eski captcha kodi allaqachon
    // "ishlatilgan" hisoblanadi, xuddi shu kod bilan qayta yuborib
    // bo'lmaydi).
    ////////////////////////////////////////////////////////
    public function lookupVehiclePolicies(
        string $tpSeria,
        string $tpNumber,
        string $govNumber,
        string $openaiApiKey,
        int $maxAttempts = 5
    ): string {
        $lastHtml = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $indexHtml = $this->openIndex();
            $csrf      = $this->extractCsrfToken($indexHtml);

            if (!$csrf) {
                throw new Exception('_csrf sahifadan topilmadi');
            }

            $captchaFile = $this->loadCaptcha();
            $code        = $this->solveCaptchaWithOpenAI($captchaFile, $openaiApiKey);

            $lastHtml = $this->submitVehicleForm($csrf, $tpSeria, $tpNumber, $govNumber, $code);

            if (!$this->isCaptchaInvalid($lastHtml)) {
                return $lastHtml;
            }
        }

        throw new Exception("Captcha {$maxAttempts} marta noto'g'ri aniqlandi, so'rov bekor qilindi");
    }

    ////////////////////////////////////////////////////////
    // CAPTCHA NOTO'G'RI KIRITILGANMI
    //
    // DIQQAT: VehicleForm forma (shu jumladan verifyCode input'i) HAR
    // DOIM javob HTML'ida qoladi — muvaffaqiyatli so'rovda ham natijalar
    // bilan birga chiqadi, shuning uchun uni tekshirish yaroqsiz. Xabar
    // matni "Rasmdagi kod notoʼgʼri kiritilgan" ham HAR DOIM (hatto
    // muvaffaqiyatli javobda ham) sahifadagi yii.validation.captcha()
    // JS konfiguratsiyasida (JSON string sifatida) uchraydi — shuning
    // uchun oddiy matn qidirish yolg'on-true beradi. Faqat xato
    // yuz berganda bu matn '<div class="help-block">...</div>' ICHIDA
    // chiqadi (muvaffaqiyatli javobda help-block bo'sh qoladi), shuning
    // uchun aynan shu o'rov bilan birga tekshiramiz (2026-09-23
    // sinovida — muvaffaqiyatli javobda false, xato javobida true
    // ekani tasdiqlangan).
    ////////////////////////////////////////////////////////
    private function isCaptchaInvalid(string $html): bool
    {
        return strpos(
            $html,
            '<div class="help-block">Rasmdagi kod notoʼgʼri kiritilgan</div>'
        ) !== false;
    }

    ////////////////////////////////////////////////////////
    // POLISALAR RO'YXATINI HTML JAVOBIDAN AJRATIB OLISH
    //
    // Har bir polis alohida accordion .card ichida, unda class'i
    // "table-borderless table-result" bo'lgan <table> bor — har bir
    // qatori <tr><th>Label:</th><td>Qiymat</td></tr>. Shu jadvaldagi
    // barcha th/td juftlarini label => qiymat ko'rinishida yig'amiz;
    // "PDF versiyasiga havola" qatoridagi <a href> esa alohida
    // pdf_link/policy_id/pin kalitlari sifatida ajratib olinadi.
    ////////////////////////////////////////////////////////
    public function extractPolicies(string $html): array
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_use_internal_errors(false);

        $xpath  = new DOMXPath($dom);
        $tables = $xpath->query(
            '//table[contains(concat(" ", normalize-space(@class), " "), " table-result ")]'
        );

        $policies = [];

        foreach ($tables as $table) {
            $policy = [];

            foreach ($xpath->query('.//tr', $table) as $row) {
                $th = $xpath->query('.//th', $row)->item(0);
                $td = $xpath->query('.//td', $row)->item(0);

                if (!$th || !$td) {
                    continue;
                }

                $label = trim(trim($th->textContent), " \t\n\r\0\x0B:");
                $link  = $xpath->query('.//a[@href]', $td)->item(0);

                if ($link) {
                    $href = $link->getAttribute('href');
                    $query = (string) parse_url($href, PHP_URL_QUERY);
                    parse_str($query, $params);

                    $policy['pdf_link'] = $this->baseUrl . $href;
                    if (!empty($params['id'])) {
                        $policy['policy_id'] = $params['id'];
                    }
                    if (!empty($params['pin'])) {
                        $policy['pin'] = $params['pin'];
                    }

                    continue;
                }

                $policy[$label] = trim(preg_replace('/\s+/u', ' ', $td->textContent));
            }

            if ($policy) {
                $policies[] = $policy;
            }
        }

        return $policies;
    }

    ////////////////////////////////////////////////////////
    // MUDDATI TUGAMAGAN POLISALARNI FILTRLASH
    //
    // "Polisni amal qilish muddati" ustuni "dd.mm.yyyy - dd.mm.yyyy"
    // ko'rinishida keladi — oxirgi sana bugungi kundan oldin bo'lmagan
    // polisalar qaytariladi.
    ////////////////////////////////////////////////////////
    public function filterActivePolicies(array $policies): array
    {
        $today = new \DateTimeImmutable('today');

        return array_values(array_filter($policies, function (array $policy) use ($today) {
            $endDate = $this->extractEndDate($policy);

            return $endDate !== null && $endDate >= $today;
        }));
    }

    ////////////////////////////////////////////////////////
    // "NECHA OY, KUN QOLGANI" MATNI
    // extractEndDate() bilan bir xil "Polisni amal qilish muddati"
    // ustunidan oxirgi sanani o'qib, bugungi kun bilan solishtiradi.
    ////////////////////////////////////////////////////////
    public function remainingLabel(array $policy): ?string
    {
        $endDate = $this->extractEndDate($policy);

        if ($endDate === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');

        if ($endDate < $today) {
            return null;
        }

        $diff = $today->diff($endDate);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = "{$diff->y} yil";
        }
        if ($diff->m > 0) {
            $parts[] = "{$diff->m} oy";
        }
        if ($diff->d > 0 || !$parts) {
            $parts[] = "{$diff->d} kun";
        }

        return implode(' ', $parts) . ' qoldi';
    }

    ////////////////////////////////////////////////////////
    // TUGASH SANASI (Y-m-d)
    ////////////////////////////////////////////////////////
    public function endDateLabel(array $policy): ?string
    {
        $endDate = $this->extractEndDate($policy);

        return $endDate ? $endDate->format('Y-m-d') : null;
    }

    private function extractEndDate(array $policy): ?\DateTimeImmutable
    {
        $range = $policy['Polisni amal qilish muddati'] ?? null;

        if (!$range || !preg_match(
            '/(\d{2})\.(\d{2})\.(\d{4})\s*-\s*(\d{2})\.(\d{2})\.(\d{4})/',
            $range,
            $match
        )) {
            return null;
        }

        $endDate = \DateTimeImmutable::createFromFormat('d.m.Y', "{$match[4]}.{$match[5]}.{$match[6]}");

        return $endDate ? $endDate->setTime(23, 59, 59) : null;
    }

    ////////////////////////////////////////////////////////
    // UMUMIY BROWSER-LIKE HTML SO'ROV SARLAVHALARI
    ////////////////////////////////////////////////////////
    private function htmlHeaders(): array
    {
        return [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: ru,en-US;q=0.9,en;q=0.8,uz;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: ' . $this->userAgent,
        ];
    }
}
