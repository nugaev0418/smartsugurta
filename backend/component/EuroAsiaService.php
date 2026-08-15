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

    private function fetchWithRetry(callable $apiCall, int $maxAttempts = 3, int $delayMs = 500)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $apiCall();

            if ($result !== false) {
                return $result;
            }

            Yii::warning("EuroAsia so'rovi urinish {$attempt}/{$maxAttempts}: transport xatosi", 'euroasia');

            if ($attempt < $maxAttempts) {
                usleep($delayMs * 1000);
            }
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
