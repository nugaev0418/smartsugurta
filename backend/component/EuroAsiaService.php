<?php

namespace backend\component;

use backend\models\EuroAsia;
use Yii;

class EuroAsiaService
{
    private EuroAsia $eai;

    public function __construct()
    {
        $this->eai = new EuroAsia();
    }

    /**
     * Urinishlar orasida $minDelayMs–$maxDelayMs oralig'ida TASODIFIY kutadi
     * (qat'iy vaqt emas) — bir nechta so'rov bir vaqtda muvaffaqiyatsiz
     * bo'lganda hammasi bir xil zumda qayta urinib, EAI'ni yana zichlab
     * qo'ymasligi uchun.
     *
     * Qayta urinish uch holatda ishga tushadi:
     *  - sof transport xatosi ($apiCall() `false` qaytarsa — DNS/timeout/
     *    ulanish xatosi);
     *  - server/GraphQL xatosi (javob JSON'i keldi, lekin `errors` massivi
     *    bor yoki JSON umuman parslanmadi — buzilgan/bo'sh javob);
     *  - "xato yo'q, lekin ma'lumot ham yo'q" javobi (`data.xxx` null/bo'sh) —
     *    EAI hozir beqaror ishlagani uchun bu ham chinakam "topilmadi" emas,
     *    vaqtinchalik nosozlik bo'lishi mumkin, shuning uchun ATAYLAB qayta
     *    uriniladi. DIQQAT: bu — haqiqatda ham topilmaydigan (mavjud
     *    bo'lmagan) ma'lumot uchun har safar $maxAttempts marta (eng yomon
     *    holatda ~12 soniyagacha) kutish degani — tezlik o'rniga ishonchlilik
     *    ataylab tanlangan.
     */
    private function fetchWithRetry(callable $apiCall, int $maxAttempts = 10, int $minDelayMs = 400, int $maxDelayMs = 1200): string
    {
        $result = false;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $apiCall();

            if (!$this->isRetryableFailure($result)) {
                return $result;
            }

            Yii::warning("EuroAsia so'rovi urinish {$attempt}/{$maxAttempts} muvaffaqiyatsiz, qayta uriniladi", 'euroasia');

            if ($attempt < $maxAttempts) {
                usleep(random_int($minDelayMs, $maxDelayMs) * 1000);
            }
        }

        // $maxAttempts marta urinilgandan keyin ham muvaffaqiyatsiz bo'lsa —
        // xom `false`/buzilgan javob o'rniga json_decode()ga yaroqli, "xato"
        // ma'nosidagi JSON qaytariladi. Har bir *Extractor::fromApiResponse()
        // `array $apiResponse` kutadi — `json_decode(false, true)`dan kelgan
        // `null`ni uzatish PHP TypeError bilan yiqilar edi; shu o'rniga
        // GraphQLExtractor'ning mavjud `errors`-ishlovi orqali toza
        // `success=false` DTO qaytariladi.
        if ($result === false || json_decode((string) $result, true) === null) {
            return json_encode([
                'errors' => [['message' => "EuroAsia so'rovi {$maxAttempts} marta muvaffaqiyatsiz bo'ldi"]],
            ]);
        }

        return $result;
    }

    private function isRetryableFailure($result): bool
    {
        if ($result === false) {
            return true;
        }

        $decoded = json_decode($result, true);

        if (!is_array($decoded)) {
            return true;
        }

        if (!empty($decoded['errors'])) {
            return true;
        }

        // Har bir so'rov "data" ostida bitta so'rov maydonini qaytaradi
        // (masalan data.vehicleByTechPassportAndLicensePlateV2) — shu
        // maydon(lar) hammasi bo'sh/null bo'lsa, "errors" bo'lmasa ham
        // qayta uriniladi (yuqoridagi izohga qarang).
        if (empty($decoded['data']) || !array_filter($decoded['data'])) {
            return true;
        }

        return false;
    }

    public function getVehicleOwnerDTO(
        string $techSeria,
        string $techNumber,
        string $licenseNumber
    ): VehicleOwnerDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->getVehicleByTechPassportAndLicensePlate(
                $techSeria,
                $techNumber,
                $licenseNumber
            ));

        $response = json_decode($apiResult, true);


        return VehicleOwnerExtractor::fromApiResponse($response);
    }

    public function getPersonByPinflDTO(
        string $seria,
        string $number,
        string $pinfl
    ): PersonByPinflDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->getPersonByPinflV2(
                $pinfl,
                $number,
                $seria,
            ));

        $response = json_decode($apiResult, true);


        return PersonByPinflExtractor::fromApiResponse($response);
    }

    public function getPersonByBirthdateDTO(
        string $seria,
        string $number,
        string $birthdate
    ): PersonByBirthdateDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->getPersonByBirthdate(
                $birthdate,
                $number,
                $seria,
            ));

        $response = json_decode($apiResult, true);


        return PersonByBirthdateExtractor::fromApiResponse($response);
    }

    public function getCalculateOsagoDTO(
        array $drivers,
        string $seasonalInsuranceId,
        bool $driverRestriction,
        string $useTerritoryRegionId,
        string $vehicleGroupId
    ): CalculateOsagoDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->CalculateOsagoV2(
                $driverRestriction,
                $drivers,
                $useTerritoryRegionId,
                $vehicleGroupId,
                $seasonalInsuranceId,
            ));

        $response = json_decode($apiResult, true);


        return CalculateOsagoExtractor::fromApiResponse($response);
    }

    public function createOsagoDTO(
        $data
    ): CreateOsagoDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->CreateOsagoV2(
                $data
            ));



        $response = json_decode($apiResult, true);


        return CreateOsagoExtractor::fromApiResponse($response);
    }

    public function getPoliceByIdDTO(
        $id
    ): PolicyByIdDTO {
        $apiResult = $this->fetchWithRetry(fn() => $this->eai
            ->policyByIdV2(
                $id
            ));

        $response = json_decode($apiResult, true);


        return PolicyByIdExtractor::fromApiResponse($response);
    }
}
