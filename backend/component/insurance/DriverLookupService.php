<?php

namespace backend\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\KafolatService;
use backend\component\PersonByBirthdateDTO;
use common\models\Setting;
use Yii;

/**
 * Tanlangan provayder (EAI yoki Kafolat) orqali haydovchini qidiradi, plus kirishni
 * normalizatsiya qiladi — BotController va WebAppController har biri ilgari inline
 * qilgan. Chaqiruvchilar $dto->success va $dto->driverLicense'ni o'zlari tekshiradi
 * (ikkala controller ham bu ikki holatni turli xabarlar bilan ko'rsatadi, shuning
 * uchun qo'shimcha wrapper tip kiritilmagan). Kafolat guvohnoma yo'qligini
 * success=true, driverLicense=null bilan qaytaradi.
 *
 * Muvaffaqiyatli natija keshlanadi — VehicleLookupService bilan bir xil naqsh.
 */
class DriverLookupService
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

    public function lookup(string $seria, string $number, string $birthdateIso): PersonByBirthdateDTO
    {
        $seria = strtoupper(trim($seria));
        $number = trim($number);

        $isKafolat = ($this->provider ?? Setting::getLookupProvider()) === Setting::LOOKUP_KAFOLAT;

        $cacheKey = ($isKafolat ? 'kafolat_driver_' : 'eai_driver_birthdate_') . md5("{$seria}|{$number}|{$birthdateIso}");
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return new PersonByBirthdateDTO($cached);
        }

        $dto = $isKafolat
            ? ($this->kafolat ??= new KafolatService())->getPersonByBirthdateDTO($seria, $number, $birthdateIso)
            : ($this->euroAsia ??= new EuroAsiaService())->getPersonByBirthdateDTO($seria, $number, $birthdateIso);

        if ($dto->success) {
            Yii::$app->cache->set($cacheKey, get_object_vars($dto), self::CACHE_TTL);
        }

        return $dto;
    }
}
