<?php

namespace backend\component\insurance;

/**
 * Single source of truth for OSAGO seasonal-insurance options: the GUID
 * EuroAsia assigns each duration, how many days it covers, and Gross's own
 * period_type code for the same duration. Previously duplicated — with
 * matching values, confirmed by inspection — between
 * BotController::handlePoliceSeasonPage()'s inline array and
 * WebAppController::SEASONS.
 */
class SeasonalInsuranceCatalog
{
    private const SEASONS = [
        '1y' => ['id' => '8465a831-850f-4445-a995-ef71195094ab', 'days' => 365, 'period_type' => 7],
        '6m' => ['id' => '9848096e-cc12-4dbd-893b-41f2cdfc9a0e', 'days' => 180, 'period_type' => 1],
        '20d' => ['id' => '0d546748-0ba6-43bc-9ce2-1b977ad9e494', 'days' => 20, 'period_type' => 8],
    ];

    public function all(): array
    {
        return self::SEASONS;
    }

    public function byKey(string $key): ?array
    {
        return self::SEASONS[$key] ?? null;
    }

    public function keys(): array
    {
        return array_keys(self::SEASONS);
    }
}
