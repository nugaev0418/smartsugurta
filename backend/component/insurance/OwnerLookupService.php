<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\KafolatService;
use backend\component\PersonByPinflDTO;
use common\models\Setting;
use Yii;

/**
 * Tanlangan provayder (EAI yoki Kafolat) orqali egasini qidiradi, plus kirishni
 * normalizatsiya qiladi (uppercase, trim) — BotController va WebAppController har
 * biri ilgari inline qilgan.
 *
 * EAI egasini PINFL + pasport bo'yicha qidiradi. Kafolat esa avtomobil + pasport
 * bo'yicha, shuning uchun Kafolat rejimida $vehicle (govNumber, techSeria,
 * techNumber) berilishi shart; $pinfl e'tiborga olinmaydi.
 *
 * Muvaffaqiyatli natija keshlanadi — VehicleLookupService bilan bir xil naqsh.
 */
class OwnerLookupService
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

    /**
     * @param array{govNumber?:string, techSeria?:string, techNumber?:string}|null $vehicle
     */
    public function lookupByPinfl(string $seria, string $number, string $pinfl, ?array $vehicle = null): PersonByPinflDTO
    {
        $seria = strtoupper(trim($seria));
        $number = trim($number);
        $pinfl = trim($pinfl);

        $isKafolat = ($this->provider ?? Setting::getLookupProvider()) === Setting::LOOKUP_KAFOLAT;

        if ($isKafolat) {
            $vehicle = [
                'govNumber' => str_replace(' ', '', strtoupper(trim((string)($vehicle['govNumber'] ?? '')))),
                'techSeria' => strtoupper(trim((string)($vehicle['techSeria'] ?? ''))),
                'techNumber' => trim((string)($vehicle['techNumber'] ?? '')),
            ];
            $cacheKey = 'kafolat_owner_' . md5("{$seria}|{$number}|" . implode('|', $vehicle));
        } else {
            $cacheKey = 'eai_owner_pinfl_' . md5("{$seria}|{$number}|{$pinfl}");
        }

        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new PersonByPinflDTO($cached);
        }

        $dto = $isKafolat
            ? ($this->kafolat ??= new KafolatService())->getPersonByPinflDTO($seria, $number, $vehicle)
            : ($this->euroAsia ??= new EuroAsiaService())->getPersonByPinflDTO($seria, $number, $pinfl);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
