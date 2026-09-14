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
