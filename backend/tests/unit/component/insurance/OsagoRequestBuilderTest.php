<?php

namespace backend\tests\unit\component\insurance;

use backend\component\insurance\OsagoApplicationData;
use backend\component\insurance\OsagoRequestBuilder;

/**
 * Locks down the EAI/Gross payload shapes OsagoRequestBuilder produces
 * against the shapes BotController::handleConfirmPage() and
 * WebAppController::actionSubmit() built inline before the refactor —
 * both callers depend on these exact field names/nesting.
 */
class OsagoRequestBuilderTest extends \Codeception\Test\Unit
{
    private OsagoRequestBuilder $builder;

    protected function _before()
    {
        $this->builder = new OsagoRequestBuilder();
    }

    private function personApplication(): OsagoApplicationData
    {
        $d = new OsagoApplicationData();
        $d->plateNumber = '40011VBA';
        $d->techSeria = 'AAF';
        $d->techNumber = '2998242';
        $d->isOrganization = false;
        $d->ownerSeria = 'AD';
        $d->ownerNumber = '6970989';
        $d->ownerBirthDateIso = '1990-01-01T10:20:30.000Z';
        $d->districtId = 'district-1';
        $d->grossPhoneDigits9 = '901234567';
        $d->eaiPhoneNumber = '998901234567';
        $d->driverRestriction = true;
        $d->startAtIso = '2026-08-16T14:22:11.500Z';
        $d->seasonalInsuranceId = '8465a831-850f-4445-a995-ef71195094ab';
        $d->periodType = 7;
        $d->gateway = 'CLICK';
        $d->drivers = [
            ['seria' => 'AB', 'number' => '4112696', 'birthDateIso' => '1992-07-25T00:00:00.000Z', 'relativeType' => 0],
        ];

        return $d;
    }

    public function testEaiPayloadForPersonOwner()
    {
        $eai = $this->builder->buildEaiPayload($this->personApplication());

        $this->assertSame(['licenseNumber' => '40011VBA', 'techPassportNumber' => '2998242', 'techPassportSeria' => 'AAF'], $eai['vehicle']);
        $this->assertSame(['isInsurant' => false, 'type' => 'PERSON', 'person' => ['passportNumber' => '6970989', 'passportSeria' => 'AD']], $eai['owner']);
        $this->assertSame([
            'type' => 'PERSON',
            'phoneNumber' => '998901234567',
            'person' => ['passportNumber' => '6970989', 'passportSeria' => 'AD', 'passportBirthdate' => '1990-01-01T10:20:30.000Z'],
            'districtId' => 'district-1',
        ], $eai['insurant']);
        $this->assertTrue($eai['driverRestriction']);
        $this->assertSame('8465a831-850f-4445-a995-ef71195094ab', $eai['seasonalInsuranceId']);
        $this->assertSame('2026-08-16T14:22:11.500Z', $eai['startAt']);
        $this->assertSame('CLICK', $eai['billingGateway']);
    }

    public function testEaiPayloadDriverIsMappedWithRelativeId()
    {
        $eai = $this->builder->buildEaiPayload($this->personApplication());

        $this->assertSame([
            'passportBirthdate' => '1992-07-25T00:00:00.000Z',
            'passportNumber' => '4112696',
            'passportSeria' => 'AB',
            // RelativeType::NOT_RELATED (0) -> its EAI relation UUID
            'relativeId' => 'ab3391d9-a5df-4b7d-ae85-79479e9ad10b',
        ], $eai['drivers'][0]);
    }

    public function testGrossPayloadForPersonOwner()
    {
        $gross = $this->builder->buildGrossPayload($this->personApplication());

        $this->assertSame('901234567', $gross['phone']);
        $this->assertSame(['gov_number' => '40011VBA', 'seria' => 'AAF', 'number' => '2998242'], $gross['vehicle']);
        $this->assertSame(['is_org' => false, 'passport' => 'AD6970989'], $gross['owner']);
        $this->assertSame('limited', $gross['policy_type']);
        $this->assertSame(7, $gross['period_type']);
        $this->assertSame('CLICK', $gross['payment_gateway']);
    }

    public function testGrossStartDateAndDriverBirthDateAreDerivedFromIsoDatePortionOnly()
    {
        $gross = $this->builder->buildGrossPayload($this->personApplication());

        $this->assertSame('2026-08-16', $gross['start_date']);
        $this->assertSame(['document' => 'AB4112696', 'birth_date' => '1992-07-25', 'relative_type' => 0], $gross['drivers'][0]);
    }

    public function testOrganizationOwnerShape()
    {
        $org = $this->personApplication();
        $org->isOrganization = true;
        $org->organizationInn = '123456789';
        $org->ownerSeria = null;
        $org->ownerNumber = null;
        $org->driverRestriction = false;

        $eai = $this->builder->buildEaiPayload($org);
        $this->assertSame(['isInsurant' => true, 'type' => 'ORGANIZATION', 'organization' => ['inn' => '123456789']], $eai['owner']);
        $this->assertSame(['type' => 'ORGANIZATION', 'phoneNumber' => '998901234567', 'organization' => ['inn' => '123456789']], $eai['insurant']);

        $gross = $this->builder->buildGrossPayload($org);
        $this->assertSame(['is_org' => true], $gross['owner']);
        $this->assertArrayNotHasKey('passport', $gross['owner']);
        $this->assertSame('unlimited', $gross['policy_type']);
    }

    public function testIsTashkentPlate()
    {
        $this->assertTrue($this->builder->isTashkentPlate('01A123BC'));
        $this->assertTrue($this->builder->isTashkentPlate('10A123BC'));
        $this->assertFalse($this->builder->isTashkentPlate('40011VBA'));
        $this->assertFalse($this->builder->isTashkentPlate(''));
    }
}
