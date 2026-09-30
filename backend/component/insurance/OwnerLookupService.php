<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\PersonByPinflDTO;
use Yii;

/**
 * EuroAsiaService::getPersonByPinflDTO() plus the input normalization
 * (uppercase, trim) BotController and WebAppController each did inline.
 *
 * Muvaffaqiyatli natija Yii::$app->cache'da CACHE_TTL davomida keshlanadi —
 * VehicleLookupService bilan bir xil naqsh, sabab uchun o'sha faylga qarang.
 */
class OwnerLookupService
{
    private const CACHE_TTL = 86400; // 24 soat

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

        $cacheKey = 'eai_owner_pinfl_' . md5("{$seria}|{$number}|{$pinfl}");
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new PersonByPinflDTO($cached);
        }

        $dto = $this->euroAsia->getPersonByPinflDTO($seria, $number, $pinfl);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
