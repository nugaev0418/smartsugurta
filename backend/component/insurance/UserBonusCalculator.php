<?php

namespace backend\component\insurance;

use common\models\Setting;

/**
 * Foydalanuvchi bonusining yagona formulasi: to'lovdan oldin ko'rsatiladigan
 * (bot, Web App, buyurtmalar kanali) va polisa tayyor bo'lgach haqiqatan
 * yoziladigan (PoliceController::addBonuse) bonus bir xil bo'lishi uchun.
 *
 * Toshkent (01/10) raqamlariga Setting::getTashkentUserPercent(), qolganlariga
 * Setting::getUserPercent() qo'llanadi. Summa pastga kesiladi.
 */
class UserBonusCalculator
{
    public function __construct(
        private ?int $userPercent = null,
        private ?int $tashkentPercent = null
    ) {
    }

    public function percentFor(string $plateNumber): int
    {
        $plate = str_replace(' ', '', strtoupper(trim($plateNumber)));

        if ((new OsagoRequestBuilder())->isTashkentPlate($plate)) {
            return $this->tashkentPercent ?? Setting::getTashkentUserPercent();
        }

        return $this->userPercent ?? Setting::getUserPercent();
    }

    /**
     * @param int|float $amount so'mda
     * @return array{percent:int, amount:int}
     */
    public function calculate($amount, string $plateNumber): array
    {
        $percent = $this->percentFor($plateNumber);

        return ['percent' => $percent, 'amount' => (int)($amount * $percent / 100)];
    }
}
