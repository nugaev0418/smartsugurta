<?php

namespace backend\component\insurance;

use backend\component\RelativeType;

/**
 * Builds the EuroAsia and Gross submission payloads from one
 * OsagoApplicationData — previously duplicated (with matching shapes,
 * confirmed by inspection) between BotController::handleConfirmPage() and
 * WebAppController::actionSubmit().
 */
class OsagoRequestBuilder
{
    public function buildEaiPayload(OsagoApplicationData $d): array
    {
        $drivers = [];
        foreach ($d->drivers as $driver) {
            $drivers[] = [
                'passportBirthdate' => $driver['birthDateIso'],
                'passportNumber' => $driver['number'],
                'passportSeria' => $driver['seria'],
                'relativeId' => RelativeType::eaiId($driver['relativeType']),
            ];
        }

        $vehicle = [
            'licenseNumber' => $d->plateNumber,
            'techPassportNumber' => $d->techNumber,
            'techPassportSeria' => $d->techSeria,
        ];

        if ($d->isOrganization) {
            $owner = [
                'isInsurant' => true,
                'type' => 'ORGANIZATION',
                'organization' => [
                    'inn' => $d->organizationInn,
                ],
            ];
            $insurant = [
                'type' => 'ORGANIZATION',
                'phoneNumber' => $d->eaiPhoneNumber,
                'organization' => [
                    'inn' => $d->organizationInn,
                ],
            ];
        } else {
            $owner = [
                'isInsurant' => false,
                'type' => 'PERSON',
                'person' => [
                    'passportNumber' => $d->ownerNumber,
                    'passportSeria' => $d->ownerSeria,
                ],
            ];
            $insurant = [
                'type' => 'PERSON',
                'phoneNumber' => $d->eaiPhoneNumber,
                'person' => [
                    'passportNumber' => $d->ownerNumber,
                    'passportSeria' => $d->ownerSeria,
                    'passportBirthdate' => $d->ownerBirthDateIso,
                ],
                'districtId' => $d->districtId,
            ];
        }

        return [
            'vehicle' => $vehicle,
            'owner' => $owner,
            'insurant' => $insurant,
            'drivers' => $drivers,
            'billingGateway' => $d->gateway,
            'driverRestriction' => $d->driverRestriction,
            'seasonalInsuranceId' => $d->seasonalInsuranceId,
            'startAt' => $d->startAtIso,
        ];
    }

    public function buildGrossPayload(OsagoApplicationData $d): array
    {
        $owner = ['is_org' => $d->isOrganization];
        if (!$d->isOrganization) {
            $owner['passport'] = $d->ownerSeria . $d->ownerNumber;
        }

        $drivers = [];
        foreach ($d->drivers as $driver) {
            $drivers[] = [
                'document' => $driver['seria'] . $driver['number'],
                'birth_date' => substr($driver['birthDateIso'], 0, 10),
                'relative_type' => $driver['relativeType'],
            ];
        }

        return [
            'phone' => $d->grossPhoneDigits9,
            'vehicle' => [
                'gov_number' => $d->plateNumber,
                'seria' => $d->techSeria,
                'number' => $d->techNumber,
            ],
            'owner' => $owner,
            'policy_type' => $d->driverRestriction ? 'limited' : 'unlimited',
            'start_date' => substr($d->startAtIso, 0, 10),
            'period_type' => $d->periodType,
            'drivers' => $drivers,
            'payment_gateway' => $d->gateway,
        ];
    }

    /**
     * SeasonalInsuranceCatalog'dagi (Gross) period_type kodi → Uzbekinvest
     * P252_PERIOD kodi. Uzbekinvest: 1 = 6 oy, 2 = 12 oy, 3 = 20 kun.
     */
    private const UZBEKINVEST_PERIOD = [
        7 => '2', // 1 yil
        1 => '1', // 6 oy
        8 => '3', // 20 kun
    ];

    /**
     * UzbekInvestOsago::run()/createPolicy() kutgan kirish massivi.
     * Sanalar DD.MM.YYYY, pasport seriya+raqam birga ("AB1234567").
     */
    public function buildUzbekinvestPayload(OsagoApplicationData $d): array
    {
        if (!isset(self::UZBEKINVEST_PERIOD[$d->periodType])) {
            throw new \InvalidArgumentException("Uzbekinvest uchun noma'lum period_type: {$d->periodType}");
        }

        $phone = $d->grossPhoneDigits9; // 9 raqam: 911311767
        $payload = [
            'tech_sery' => $d->techSeria,
            'tech_number' => $d->techNumber,
            'gov_number' => $d->plateNumber,
            'phone' => sprintf(
                '+998 (%s) %s-%s-%s',
                substr($phone, 0, 2), substr($phone, 2, 3), substr($phone, 5, 2), substr($phone, 7, 2)
            ),
            'start_date' => self::isoToDmy($d->startAtIso),
            'period' => self::UZBEKINVEST_PERIOD[$d->periodType],
            'policy_type' => $d->driverRestriction ? '2' : '1',
            'drivers' => [],
        ];

        if (!$d->isOrganization) {
            $payload['owner_passport'] = $d->ownerSeria . $d->ownerNumber;
            $payload['owner_birthdate'] = self::isoToDmy($d->ownerBirthDateIso);
        }

        if ($d->driverRestriction) {
            foreach ($d->drivers as $driver) {
                $payload['drivers'][] = [
                    'passport' => $driver['seria'] . $driver['number'],
                    'birthdate' => self::isoToDmy($driver['birthDateIso']),
                ];
            }
        }

        return $payload;
    }

    private static function isoToDmy(string $iso): string
    {
        return \DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10))->format('d.m.Y');
    }

    /**
     * Toshkent (01/10) davlat raqami — WebAppController::actionSubmit() bu
     * asosda EAI'ga to'g'ridan-to'g'ri yuboradi. Faqat plastinka prefiksini
     * tekshiradi; bu tekshiruvni haqiqatan qo'llash-qo'llamaslik
     * (OsagoSubmissionService::submit()ning $allowDirectEaiForTashkent
     * parametri) chaqiruvchiga bog'liq.
     */
    public function isTashkentPlate(string $plateNumber): bool
    {
        $prefix = substr($plateNumber, 0, 2);
        return in_array($prefix, ['01', '10'], true);
    }
}
