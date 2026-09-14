<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\PersonByPinflDTO;

/**
 * EuroAsiaService::getPersonByPinflDTO() plus the input normalization
 * (uppercase, trim) BotController and WebAppController each did inline.
 */
class OwnerLookupService
{
    private EuroAsiaService $euroAsia;

    public function __construct(?EuroAsiaService $euroAsia = null)
    {
        $this->euroAsia = $euroAsia ?? new EuroAsiaService();
    }

    public function lookupByPinfl(string $seria, string $number, string $pinfl): PersonByPinflDTO
    {
        $seria = strtoupper(trim($seria));
        $number = trim($number);
        $pinfl = trim($pinfl);

        return $this->euroAsia->getPersonByPinflDTO($seria, $number, $pinfl);
    }
}
