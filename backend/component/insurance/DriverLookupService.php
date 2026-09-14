<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\PersonByBirthdateDTO;

/**
 * EuroAsiaService::getPersonByBirthdateDTO() plus the input normalization
 * BotController and WebAppController each did inline. Callers still check
 * $dto->success and $dto->driverLicense themselves (both controllers already
 * render those two cases with different messages, so no extra wrapper type
 * is introduced here).
 */
class DriverLookupService
{
    private EuroAsiaService $euroAsia;

    public function __construct(?EuroAsiaService $euroAsia = null)
    {
        $this->euroAsia = $euroAsia ?? new EuroAsiaService();
    }

    public function lookup(string $seria, string $number, string $birthdateIso): PersonByBirthdateDTO
    {
        $seria = strtoupper(trim($seria));
        $number = trim($number);

        return $this->euroAsia->getPersonByBirthdateDTO($seria, $number, $birthdateIso);
    }
}
