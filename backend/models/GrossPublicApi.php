<?php

namespace backend\models;

/**
 * Gross'ning login/captcha talab qilmaydigan OCHIQ REST API'si
 * (api-prod.gross.uz — gross.uz saytining o'z OSAGO kalkulyatori
 * ishlatadigan API, Origin: https://gross.uz sifatida). `EuroAsia.php`
 * bilan bir xil uslub: xom javobni dekodlamasdan JSON string sifatida
 * qaytaradi, dekodlash/xatolikni aniqlash chaqiruvchiga qoldiriladi
 * (`$decoded['error'] === 0` — muvaffaqiyat, `1` — xato, xato holatida
 * `code`/`message` bor, `data` bo'sh massiv).
 *
 * MUHIM: bu klass faqat foydalanuvchi kiritgan ma'lumotlarni TEKSHIRISH
 * uchun (bot va Web App'da) — bu asosida sug'urta shartnomasi TUZILMAYDI.
 * Haqiqiy shartnoma yaratish uchun `backend/gross/GrossOsago.php` (login+
 * captcha+cookie-sessiya bilan ishlaydigan kabinet klienti) ishlatiladi —
 * bu ikkalasi butunlay alohida integratsiyalar.
 */
class GrossPublicApi
{
    private const DEFAULT_CALC_PAYLOAD = [
        'autotype' => 1,
        'region' => 1,
        'period' => 1,
        'citizen' => 1,
        'number' => 4,
        'promo' => null,
        'coeff' => 1,
    ];

    private string $baseUrl = 'https://api-prod.gross.uz/api/v1';

    private function request(string $path, array $data): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'accept: application/json, text/plain, */*',
                'accept-language: ru,en-US;q=0.9,en;q=0.8,uz;q=0.7',
                'cache-control: no-cache',
                'content-type: application/json',
                'lang: uz',
                'origin: https://gross.uz',
                'pragma: no-cache',
                'priority: u=1, i',
                'referer: https://gross.uz/products/osago',
                'sec-ch-ua: "Google Chrome";v="153", "Not_A Brand";v="8", "Chromium";v="153"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-site',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Davlat raqami + texpasport seriya/raqami bo'yicha avtomobil/egasi
     * ma'lumotlarini tekshiradi. Topilmasa {"error":1,"code":"napp_not_found",...}.
     */
    public function checkTechData(
        string $govNumber,
        string $techPassSeries,
        string $techPassNumber,
        array $payload = self::DEFAULT_CALC_PAYLOAD
    ): string {
        return $this->request('/osago/check-tech-data', [
            'tech_data' => [
                'autonumber' => $govNumber,
                'tech_pass_series' => $techPassSeries,
                'tech_pass_number' => $techPassNumber,
            ],
            'payload' => $payload,
        ]);
    }

    /**
     * Passport seriya/raqami + PINFL bo'yicha shaxsni tekshiradi
     * (haydovchilik guvohnomasi ma'lumotlarini ham qaytaradi).
     */
    public function getPersonByPinfl(string $passSeria, string $passNumber, string $pinfl): string
    {
        return $this->request('/gross-provider/get-data', [
            'is_ersp' => false,
            'method' => 'pass-data-pinfl',
            'payload' => [
                'pass_sery' => $passSeria,
                'pass_number' => $passNumber,
                'pinfl' => $pinfl,
            ],
        ]);
    }

    /**
     * Passport seriya/raqami + tug'ilgan sana ("Y-m-d") bo'yicha shaxsni
     * tekshiradi. Diqqat: shaxs topilsa-yu haydovchilik guvohnomasi
     * bo'lmasa ham javob `error: 0` bo'ladi, faqat `licenseNumber`/
     * `licenseSeria`/`licenseIssueDate` `null` bo'ladi — buni chaqiruvchi
     * o'zi tekshirishi kerak.
     */
    public function getPersonByBirthday(string $passSeria, string $passNumber, string $birthday): string
    {
        return $this->request('/gross-provider/get-data', [
            'is_ersp' => false,
            'method' => 'pass-data-birthday',
            'payload' => [
                'pass_sery' => $passSeria,
                'pass_number' => $passNumber,
                'birthday' => $birthday,
            ],
        ]);
    }

    /**
     * OSAGO sug'urta summasini hisoblaydi (tekshirish/ma'lumot uchun —
     * haqiqiy shartnoma summasi emas).
     */
    public function calculateOsago(array $payload = self::DEFAULT_CALC_PAYLOAD): string
    {
        return $this->request('/osago/calc', $payload);
    }
}
