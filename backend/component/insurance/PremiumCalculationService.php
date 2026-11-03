<?php

namespace backend\component\insurance;

use backend\component\CalculateOsagoDTO;
use backend\component\EuroAsiaService;
use backend\component\KafolatService;
use common\models\Setting;

/**
 * Narx hisoblashning yagona kirish nuqtasi: Setting::getLookupProvider()ga qarab
 * EAI (useTerritoryRegionId + vehicleGroupId bo'yicha) yoki Kafolat (avtomobil
 * davlat raqami + texpasport bo'yicha) orqali hisoblaydi. Ikkalasi ham
 * CalculateOsagoDTO qaytaradi, premium TIYINda (chaqiruvchilar `/ 100` qiladi).
 */
class PremiumCalculationService
{
    private ?EuroAsiaService $euroAsia;
    private ?KafolatService $kafolat;
    private ?int $provider;

    public function __construct(?EuroAsiaService $euroAsia = null, ?KafolatService $kafolat = null, ?int $provider = null)
    {
        $this->euroAsia = $euroAsia;
        $this->kafolat = $kafolat;
        $this->provider = $provider;
    }

    public function isKafolat(): bool
    {
        return ($this->provider ?? Setting::getLookupProvider()) === Setting::LOOKUP_KAFOLAT;
    }

    /**
     * @param array{govNumber?:string, techSeria?:string, techNumber?:string} $vehicle Kafolat uchun majburiy
     * @param string|null $startDate 'Y-m-d' (Kafolat uchun; bo'sh bo'lsa bugun)
     */
    public function calculate(
        string $seasonalInsuranceId,
        bool $driverRestriction,
        ?string $useTerritoryRegionId,
        ?string $vehicleGroupId,
        array $vehicle = [],
        ?string $startDate = null
    ): CalculateOsagoDTO {
        if ($this->isKafolat()) {
            if (empty($vehicle['govNumber']) || empty($vehicle['techSeria']) || empty($vehicle['techNumber'])) {
                return new CalculateOsagoDTO(['success' => false]);
            }

            return ($this->kafolat ??= new KafolatService())->getCalculateOsagoDTO(
                [
                    'govNumber' => str_replace(' ', '', strtoupper(trim($vehicle['govNumber']))),
                    'techSeria' => strtoupper(trim($vehicle['techSeria'])),
                    'techNumber' => trim($vehicle['techNumber']),
                ],
                $seasonalInsuranceId,
                $driverRestriction,
                $startDate
            );
        }

        return ($this->euroAsia ??= new EuroAsiaService())->getCalculateOsagoDTO(
            [],
            $seasonalInsuranceId,
            $driverRestriction,
            (string)$useTerritoryRegionId,
            (string)$vehicleGroupId
        );
    }
}
