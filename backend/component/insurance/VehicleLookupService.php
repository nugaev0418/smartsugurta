<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\VehicleOwnerDTO;
use Yii;

/**
 * EuroAsiaService::getVehicleOwnerDTO() plus the input normalization
 * (uppercase, trim, strip spaces) BotController and WebAppController each
 * did inline, in slightly different order but to the same effect.
 *
 * Muvaffaqiyatli natija Yii::$app->cache'da CACHE_TTL davomida keshlanadi —
 * xuddi shu avtomobil/texpasport uchun keyingi so'rov EAI'ga umuman
 * yubormasdan keshdan qaytadi (API beqaror ishlagani + tezlik uchun).
 * "Topilmadi" natija ataylab keshlanmaydi.
 */
class VehicleLookupService
{
    private const CACHE_TTL = 86400; // 24 soat

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

        $cacheKey = 'eai_vehicle_' . md5("{$techSeria}|{$techNumber}|{$licenseNumber}");
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new VehicleOwnerDTO($cached);
        }

        $dto = $this->euroAsia->getVehicleOwnerDTO($techSeria, $techNumber, $licenseNumber);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
