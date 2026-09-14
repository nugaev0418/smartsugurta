<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\VehicleOwnerDTO;

/**
 * EuroAsiaService::getVehicleOwnerDTO() plus the input normalization
 * (uppercase, trim, strip spaces) BotController and WebAppController each
 * did inline, in slightly different order but to the same effect.
 */
class VehicleLookupService
{
    private EuroAsiaService $euroAsia;

    public function __construct(?EuroAsiaService $euroAsia = null)
    {
        $this->euroAsia = $euroAsia ?? new EuroAsiaService();
    }

    public function lookup(string $techSeria, string $techNumber, string $licenseNumber): VehicleOwnerDTO
    {
        $techSeria = strtoupper(trim($techSeria));
        $techNumber = trim($techNumber);
        $licenseNumber = str_replace(' ', '', strtoupper(trim($licenseNumber)));

        return $this->euroAsia->getVehicleOwnerDTO($techSeria, $techNumber, $licenseNumber);
    }
}
