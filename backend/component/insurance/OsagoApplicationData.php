<?php

namespace backend\component\insurance;

use backend\component\bot\BotContext;
use backend\controllers\BotController;

/**
 * Everything OsagoRequestBuilder needs to build both the EuroAsia and Gross
 * submission payloads, in a shape both BotController's chat FSM and
 * WebAppController's Mini App API can populate from their own native data.
 *
 * Dates are carried as ISO strings (startAtIso, drivers[]['birthDateIso']) —
 * not separate Y-m-d fields — because both callers' Y-m-d value is always
 * recoverable as substr($iso, 0, 10) (proven by how each caller derives its
 * ISO value: DateTime::createFromFormat()->format() round-trips the date
 * portion losslessly; only the time-of-day, which nothing here reads, can
 * vary). Carrying one field instead of two removes a place for the two to
 * drift out of sync.
 */
class OsagoApplicationData
{
    public string $plateNumber;
    public string $techSeria;
    public string $techNumber;

    public bool $isOrganization;
    public ?string $organizationInn = null;

    public ?string $ownerSeria = null;
    public ?string $ownerNumber = null;
    public ?string $ownerBirthDateIso = null;
    public ?string $districtId = null;

    /** Last 9 digits, no country code — Gross's 'phone' field. */
    public string $grossPhoneDigits9;
    /** EAI's insurant.phoneNumber — each caller supplies its own existing format. */
    public string $eaiPhoneNumber;

    public bool $driverRestriction;
    public string $startAtIso;
    public string $seasonalInsuranceId;
    public int $periodType;
    public string $gateway;

    /** @var array<int, array{seria:string, number:string, birthDateIso:string, relativeType:int}> */
    public array $drivers = [];

    /**
     * Maps BotContext's FSM state (Botuser.data fields) into this shape.
     * Mirrors ConfirmStageHandler's original inline $eaiData/$grossPoliceData
     * construction field-for-field.
     */
    public static function fromBotState(BotContext $ctx): self
    {
        $d = new self();

        $d->plateNumber = $ctx->lisenceNumber;
        $d->techSeria = $ctx->texPassSeria;
        $d->techNumber = $ctx->texPassNumber;

        $vehicleData = $ctx->vehicleData;
        $d->isOrganization = $vehicleData['ownerType'] !== 'PERSON';
        $d->organizationInn = $vehicleData['inn'] ?? null;

        if (!$d->isOrganization) {
            $ownerData = $ctx->ownerData;
            $d->ownerSeria = $ownerData['seria'];
            $d->ownerNumber = $ownerData['number'];
            $d->ownerBirthDateIso = $ownerData['birthDate'];
            $d->districtId = $ownerData['districtId'];
        }

        $d->eaiPhoneNumber = $ctx->phone;
        $d->grossPhoneDigits9 = substr($ctx->phone, -9);

        $d->driverRestriction = $ctx->driverRestriction == 'Limited';
        $d->startAtIso = BotController::toIsoDate($ctx->startAt);
        $d->seasonalInsuranceId = $ctx->policeSeason['id'];
        $d->gateway = $ctx->paymentType;

        $policeDataLocal = $ctx->police_data;
        $d->periodType = (int)(is_array($policeDataLocal) ? ($policeDataLocal['period_type'] ?? 0) : 0);
        $policeDrivers = is_array($policeDataLocal) ? ($policeDataLocal['drivers'] ?? []) : [];

        $drivers = [];
        if ($ctx->drivers != '') {
            foreach ($ctx->drivers as $i => $driver) {
                $drivers[] = [
                    'seria' => $driver['seria'],
                    'number' => $driver['number'],
                    'birthDateIso' => $driver['birthDate'],
                    'relativeType' => (int)($policeDrivers[$i]['relative_type'] ?? 0),
                ];
            }
        }
        $d->drivers = $drivers;

        return $d;
    }
}
