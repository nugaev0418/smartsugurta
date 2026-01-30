<?php

namespace backend\component;

use backend\models\EuroAsia;

class EuroAsiaService
{
    private EuroAsia $eai;

    public function __construct()
    {
        $this->eai = new EuroAsia();
    }

    public function getVehicleOwnerDTO(
        string $techSeria,
        string $techNumber,
        string $licenseNumber
    ): VehicleOwnerDTO {
        $apiResult = $this->eai
            ->getVehicleByTechPassportAndLicensePlate(
                $techSeria,
                $techNumber,
                $licenseNumber
            );

        $response = json_decode($apiResult, true);


        return VehicleOwnerExtractor::fromApiResponse($response);
    }

    public function getPersonByPinflDTO(
        string $seria,
        string $number,
        string $pinfl
    ): PersonByPinflDTO {
        $apiResult = $this->eai
            ->getPersonByPinflV2(
                $pinfl,
                $number,
                $seria,
            );

        $response = json_decode($apiResult, true);


        return PersonByPinflExtractor::fromApiResponse($response);
    }

    public function getPersonByBirthdateDTO(
        string $seria,
        string $number,
        string $birthdate
    ): PersonByBirthdateDTO {
        $apiResult = $this->eai
            ->getPersonByBirthdate(
                $birthdate,
                $number,
                $seria,
            );

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
        $apiResult = $this->eai
            ->CalculateOsagoV2(
                $driverRestriction,
                $drivers,
                $useTerritoryRegionId,
                $vehicleGroupId,
                $seasonalInsuranceId,
            );

        $response = json_decode($apiResult, true);


        return CalculateOsagoExtractor::fromApiResponse($response);
    }

    public function createOsagoDTO(
        $data
    ): CreateOsagoDTO {
        $apiResult = $this->eai
            ->CreateOsagoV2(
                $data
            );



//        \Yii::error($data);
//        \Yii::error($apiResult);


        $response = json_decode($apiResult, true);


        return CreateOsagoExtractor::fromApiResponse($response);
    }

    public function getPoliceByIdDTO(
        $id
    ): policyByIdDTO {
        $apiResult = $this->eai
            ->policyByIdV2(
                $id
            );

        $response = json_decode($apiResult, true);


        return PolicyByIdExtractor::fromApiResponse($response);
    }
}
