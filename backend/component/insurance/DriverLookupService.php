<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\PersonByBirthdateDTO;
use Yii;

/**
 * EuroAsiaService::getPersonByBirthdateDTO() plus the input normalization
 * BotController and WebAppController each did inline. Callers still check
 * $dto->success and $dto->driverLicense themselves (both controllers already
 * render those two cases with different messages, so no extra wrapper type
 * is introduced here).
 *
 * Muvaffaqiyatli natija Yii::$app->cache'da CACHE_TTL davomida keshlanadi —
 * VehicleLookupService bilan bir xil naqsh, sabab uchun o'sha faylga qarang.
 */
class DriverLookupService
{
    private const CACHE_TTL = 86400; // 24 soat

    private EuroAsiaService $euroAsia;

    public function __construct(?EuroAsiaService $euroAsia = null)
    {
        $this->euroAsia = $euroAsia ?? new EuroAsiaService();
    }

    public function lookup(string $seria, string $number, string $birthdateIso): PersonByBirthdateDTO
    {
        $seria = strtoupper(trim($seria));
        $number = trim($number);

        $cacheKey = 'eai_driver_birthdate_' . md5("{$seria}|{$number}|{$birthdateIso}");
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new PersonByBirthdateDTO($cached);
        }

        $dto = $this->euroAsia->getPersonByBirthdateDTO($seria, $number, $birthdateIso);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
