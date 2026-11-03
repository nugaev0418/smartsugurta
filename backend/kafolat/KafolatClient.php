<?php

namespace backend\kafolat;

/**
 * kafolat.uz saytining ochiq JSON API'si uchun past darajali HTTP klient.
 *
 * Login/captcha/cookie talab qilinmaydi (sayt brauzeri ham anonim so'rov yuboradi).
 * Yii'ga bog'liq emas: baseUrl va timeout'lar konstruktor orqali beriladi.
 *
 * Transport xatosida (curl, bo'sh/yaroqsiz JSON) \RuntimeException tashlanadi.
 * Biznes xatolari (masalan "topilmadi") HTTP 4xx bilan keladi va istisno EMAS —
 * ular dekodlangan javob tanasida qaytadi (`success=false`, `error.code`),
 * chunki chaqiruvchi ularni `error.code` bo'yicha farqlaydi.
 */
class KafolatClient
{
    public function __construct(
        private string $baseUrl = 'https://kafolat.uz',
        private bool $verifySsl = true,
        private int $timeout = 10,
        private int $connectTimeout = 3
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @return array{http:int, body:array} dekodlangan JSON tanasi va HTTP kodi
     * @throws \RuntimeException
     */
    public function post(string $path, array $payload): array
    {
        $ch = curl_init($this->baseUrl . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Origin: ' . $this->baseUrl,
                'Referer: ' . $this->baseUrl . '/uz/products/osago',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            ],
        ]);

        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Kafolat so\'rovi bajarilmadi: ' . $error);
        }

        $body = json_decode((string)$raw, true);
        if (!is_array($body)) {
            throw new \RuntimeException("Kafolat yaroqsiz javob qaytardi (HTTP {$http})");
        }

        return ['http' => $http, 'body' => $body];
    }
}
