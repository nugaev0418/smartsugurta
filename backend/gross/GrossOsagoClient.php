<?php

namespace backend\gross;
use DOMDocument;
use Exception;

class GrossOsagoClient
{
    private string $baseUrl = 'https://osago.gross.uz';
    private string $cookieFile;

    public function __construct()
    {
        $this->cookieFile = __DIR__ . '/gross_cookie.txt';

        if (!file_exists($this->cookieFile)) {
            file_put_contents($this->cookieFile, '');
        }
    }

    ////////////////////////////////////////////////////////
    // CURL REQUEST
    ////////////////////////////////////////////////////////
    private function request(
        string $url,
        string $method = 'GET',
        array $headers = [],
        ?string $postData = null,
        bool $binary = false
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

        if (in_array($method, ['POST','PUT','PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);

        return $response;
    }

    ////////////////////////////////////////////////////////
    // 1. HOME PAGE
    ////////////////////////////////////////////////////////
    public function openHome(): string
    {
        return $this->request(
            $this->baseUrl . '/',
            'GET',
            [
                'User-Agent: Mozilla/5.0'
            ]
        );
    }

    ////////////////////////////////////////////////////////
    // CAPTCHA IMG SRC TOPISH
    ////////////////////////////////////////////////////////
    public function getCaptchaSrc(string $html): ?string
    {
        preg_match('/<img[^>]+src="([^"]*im\.php\?t=[^"]*)"/i', $html, $match);

        return $match[1] ?? null;
    }

    ////////////////////////////////////////////////////////
    // CAPTCHA IMAGE DOWNLOAD
    ////////////////////////////////////////////////////////
    public function loadCaptcha(string $src): string
    {
        $url = $this->baseUrl . '/' . ltrim($src, '/');

        $img = $this->request(
            $url,
            'GET',
            [
                'Accept: image/*',
                'Referer: https://osago.gross.uz/',
                'User-Agent: Mozilla/5.0'
            ]
        );

        if (!$img || strlen($img) < 100) {
            throw new Exception("Captcha rasm yuklanmadi");
        }

        $file = __DIR__ . '/captcha.jpg';

        file_put_contents($file, $img);
        clearstatcache();

        echo "Captcha saqlandi: {$file}\n";
        echo "Size: " . filesize($file) . " bytes\n";

        flush();

        return $file;
    }

    ////////////////////////////////////////////////////////
    // LOGIN
    ////////////////////////////////////////////////////////
    public function login(
        string $login,
        string $password,
        string $captcha
    ): string {

        $data = http_build_query([
            'UserName'   => $login,
            'myPassword' => $password,
            'a'          => 'submit',
            'code'       => $captcha,
            'submit'     => 'Войти'
        ]);


        var_dump($data);

        return $this->request(
            $this->baseUrl . '/index.php?ln=0',
            'POST',
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/',
                'User-Agent: Mozilla/5.0'
            ],
            $data
        );
    }

    ////////////////////////////////////////////////////////
    // DASHBOARD
    ////////////////////////////////////////////////////////
    public function dashboard(): string
    {
        return $this->request(
            $this->baseUrl . '/main.php?ln=2',
            'GET',
            [
                'User-Agent: Mozilla/5.0'
            ]
        );
    }


////////////////////////////////////////////////////////
// VEHICLE INFO API
////////////////////////////////////////////////////////
    public function getVehicle(
        string $seria,
        string $number,
        string $govNumber
    ): string {

        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/osago/vehicle",
            "payload" => [
                "techPassportSeria"  => $seria,
                "techPassportNumber" => $number,
                "govNumber"          => $govNumber
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }


    public function getOwner(
        string $document,
        string $pinfl,
        string $senderPinfl,
        string $transactionId,
    ): string {

        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/pinfl-v2",
            "payload" => [
                "document" => $document,
                "isConsent" => "Y",
                "pinfl"     => $pinfl,
                "senderPinfl" => $senderPinfl,
                "transactionId" => $transactionId
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    public function getIsPensioner(
        string $passportNumber,
        string $passportSeries,
        string $pinfl,
    ): string {

        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/is-pensioner",
            "payload" => [
                "pinfl"          => $pinfl,
                "passportSeries" => $passportSeries,
                "passportNumber" => $passportNumber,
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    public function phoneNumberChecker(
        string $phone_number,
        string $type,
    ): string {
        return $this->request(
            $this->baseUrl . '/Epolicy/endpoints/phoneNumberChecker.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode([
                'phone_number' => $phone_number,
                'type'         => $type,
            ], JSON_UNESCAPED_UNICODE)
        );
    }

////////////////////////////////////////////////////////
// DRIVER COEFFICIENT
////////////////////////////////////////////////////////
    public function getDriverCoefficient(string $pinfl): string
    {
        $payload = [
            "url"     => "https://erspapiv2.e-osgo.uz/api/provider/driver-coefficient",
            "payload" => ["pinfl" => $pinfl]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

////////////////////////////////////////////////////////
// PASSPORT BIRTH DATE V2
////////////////////////////////////////////////////////
    public function getPassportByBirthDate(
        string $document,
        string $birthDate,
        string $senderPinfl,
        string $transactionId,
    ): string {
        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/passport-birth-date-v2",
            "payload" => [
                "transactionId" => $transactionId,
                "isConsent"     => "Y",
                "senderPinfl"   => $senderPinfl,
                "document"      => $document,
                "birthDate"     => $birthDate,
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

////////////////////////////////////////////////////////
// DRIVER SUMMARY V2
////////////////////////////////////////////////////////
    public function getDriverSummary(
        string $document,
        string $pinfl,
        string $senderPinfl,
        string $transactionId,
    ): string {
        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/driver-summary-v2",
            "payload" => [
                "transactionId" => $transactionId,
                "isConsent"     => "Y",
                "senderPinfl"   => $senderPinfl,
                "document"      => $document,
                "pinfl"         => $pinfl,
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

////////////////////////////////////////////////////////
// OPENAI CAPTCHA SOLVER
////////////////////////////////////////////////////////
    public function solveCaptchaWithOpenAI(string $imagePath, string $openaiApiKey): string
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType  = 'image/jpeg';

        $payload = [
            'model'      => 'gpt-4o',
            'max_tokens' => 10,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
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
            CURLOPT_POSTFIELDS      => json_encode($payload),
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
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
            throw new Exception('OpenAI captcha javobini aniqlay olmadi. Javob: ' . $response);
        }

        return $text;
    }

////////////////////////////////////////////////////////
// CREATE CONTRACT
////////////////////////////////////////////////////////
    public function createContract(array $data): string
    {
        return $this->request(
            'https://api.gross.uz/api/osgo/contract/create',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }


////////////////////////////////////////////////////////
// PAYMENT — CLICK (payment_method=5)
////////////////////////////////////////////////////////
    public function payWithClick(string $uuid, string $anketaId): string
    {
        $data = http_build_query([
            'step'           => '1',
            'payment_method' => '5',
            'anketa_id'      => $anketaId,
        ]);

        return $this->request(
            $this->baseUrl . "/epolis_oplata.php?ln=2&uuid={$uuid}&anketa={$anketaId}",
            'POST',
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://osago.gross.uz',
                'Referer: ' . $this->baseUrl . "/epolis_oplata.php?ln=2&uuid={$uuid}&anketa={$anketaId}",
                'User-Agent: Mozilla/5.0',
            ],
            $data
        );
    }

////////////////////////////////////////////////////////
// PAYMENT — PAYME (payment_method=4)
////////////////////////////////////////////////////////
    public function payWithPayme(string $uuid, string $anketaId): string
    {
        $data = http_build_query([
            'step'           => '1',
            'payment_method' => '4',
            'anketa_id'      => $anketaId,
            'payme_type'     => 'cabinet',
            'ccard_number'   => '',
            'ccard_exp_date' => '',
            'phone'          => '',
        ]);

        return $this->request(
            $this->baseUrl . "/epolis_oplata.php?ln=2&uuid={$uuid}&anketa={$anketaId}",
            'POST',
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://osago.gross.uz',
                'Referer: ' . $this->baseUrl . "/epolis_oplata.php?ln=2&uuid={$uuid}&anketa={$anketaId}",
                'User-Agent: Mozilla/5.0',
            ],
            $data
        );
    }

////////////////////////////////////////////////////////
// PARSE PAYMENT LINK FROM HTML
// birinchi .text-center ichidan qidiradi, topilmasa ikkinchidan
////////////////////////////////////////////////////////
    public function extractPaymentLink(string $html): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $base = '//div[contains(@class,"epolis")]'
              . '//div[contains(@class,"container")]'
              . '//div[contains(@class,"text-center")]';

        foreach ([1, 2] as $nth) {
            $nodes = $xpath->query("{$base}[{$nth}]//a/@href");
            if ($nodes && $nodes->length > 0) {
                $href   = $nodes->item(0)->nodeValue;
                $parsed = parse_url($href);
                parse_str($parsed['query'] ?? '', $params);
                unset($params['return_url'], $params['card_type']);
                $query = http_build_query($params);
                return $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path']
                     . ($query ? '?' . $query : '');
            }
        }

        return null;
    }

////////////////////////////////////////////////////////
// GET COMPANY BY INN
////////////////////////////////////////////////////////
    public function getCompanyByInn(string $inn): string
    {
        $payload = [
            "url" => "https://erspapiv2.e-osgo.uz/api/provider/inn",
            "payload" => [
                "inn" => $inn
            ]
        ];

        return $this->request(
            $this->baseUrl . '/api/ersp-integration-v3/endpoints/provider.php',
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Origin: https://osago.gross.uz',
                'Referer: https://osago.gross.uz/osago/index.php?ln=2',
                'User-Agent: Mozilla/5.0'
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

}