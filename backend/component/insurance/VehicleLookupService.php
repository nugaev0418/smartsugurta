<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\KafolatService;
use backend\component\VehicleOwnerDTO;
use common\models\Setting;
use Yii;

/**
 * Tanlangan provayder (Setting::getLookupProvider(): EAI yoki Kafolat) orqali
 * avtomobil/egasini qidiradi, plus kirishni normalizatsiya qiladi
 * (uppercase, trim, bo'shliqsiz) — BotController va WebAppController har biri
 * ilgari inline qilgan.
 *
 * Muvaffaqiyatli natija Yii::$app->cache'da CACHE_TTL davomida keshlanadi —
 * xuddi shu avtomobil/texpasport uchun keyingi so'rov provayderga umuman
 * yubormasdan keshdan qaytadi (API beqaror ishlagani + tezlik uchun).
 * "Topilmadi" natija ataylab keshlanmaydi. Kesh kaliti provayderga xos, shuning
 * uchun EAI natijasi Kafolat natijasi sifatida qaytib qolmaydi.
 */
class VehicleLookupService
{
    private const CACHE_TTL = 86400; // 24 soat

    private ?EuroAsiaService $euroAsia;
    private ?KafolatService $kafolat;
    private ?int $provider;

    public function __construct(?EuroAsiaService $euroAsia = null, ?KafolatService $kafolat = null, ?int $provider = null)
    {
        $this->euroAsia = $euroAsia;
        $this->kafolat = $kafolat;
        $this->provider = $provider;
    }

    public function lookup(string $techSeria, string $techNumber, string $licenseNumber): VehicleOwnerDTO
    {
        $techSeria = strtoupper(trim($techSeria));
        $techNumber = trim($techNumber);
        $licenseNumber = str_replace(' ', '', strtoupper(trim($licenseNumber)));

        $isKafolat = ($this->provider ?? Setting::getLookupProvider()) === Setting::LOOKUP_KAFOLAT;

        $cacheKey = ($isKafolat ? 'kafolat_vehicle_' : 'eai_vehicle_') . md5("{$techSeria}|{$techNumber}|{$licenseNumber}");
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new VehicleOwnerDTO($cached);
        }

        $dto = $isKafolat
            ? ($this->kafolat ??= new KafolatService())->getVehicleOwnerDTO($techSeria, $techNumber, $licenseNumber)
            : ($this->euroAsia ??= new EuroAsiaService())->getVehicleOwnerDTO($techSeria, $techNumber, $licenseNumber);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
