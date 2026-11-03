<?php

namespace backend\tests\unit\component;

use backend\component\insurance\DriverLookupService;
use backend\component\insurance\PremiumCalculationService;
use backend\component\insurance\SeasonalInsuranceCatalog;
use backend\component\KafolatService;
use backend\kafolat\KafolatClient;
use common\models\Setting;

/**
 * Javob shakllari kafolat.uz saytidan olingan haqiqiy so'rovlardan (qiymatlar
 * maskalangan, shaxsiy ma'lumotlar yo'q).
 */
class KafolatServiceTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        \Yii::$app->cache->flush();
    }

    /** @param array<string,array{http:int,body:array}> $responses path => javob */
    private function client(array $responses, ?array &$calls = null): KafolatClient
    {
        $calls = [];

        return new class($responses, $calls) extends KafolatClient {
            public function __construct(private array $responses, private array &$calls) {}

            public function post(string $path, array $payload): array
            {
                $this->calls[] = [$path, $payload];
                $r = $this->responses[$path] ?? null;
                if ($r instanceof \Throwable) {
                    throw $r;
                }
                return $r ?? ['http' => 500, 'body' => []];
            }
        };
    }

    public function testVehicleMapsPersonOwner()
    {
        $svc = new KafolatService($this->client(['/api/Osago/vehicle' => ['http' => 200, 'body' => [
            'success' => true, 'error' => null,
            'result' => ['govNumber' => '30W214PB', 'modelName' => 'DAMAS-2', 'ownerFullName' => 'KASIMOV FERUZ DJURAYEVICH', 'isIndividual' => true],
        ]]]));

        $dto = $svc->getVehicleOwnerDTO('AAG', '1234567', '30W214PB');

        $this->assertTrue($dto->success);
        $this->assertSame('PERSON', $dto->ownerType);
        $this->assertSame('FERUZ', $dto->firstName);
        $this->assertSame('KASIMOV', $dto->lastName);
        $this->assertSame('DJURAYEVICH', $dto->middleName);
        $this->assertSame('DAMAS-2', $dto->model);
        $this->assertNull($dto->pinfl);
    }

    public function testVehicleMapsOrganizationOwner()
    {
        $svc = new KafolatService($this->client(['/api/Osago/vehicle' => ['http' => 200, 'body' => [
            'success' => true,
            'result' => ['govNumber' => '01A001AA', 'modelName' => 'X', 'ownerFullName' => 'MCHJ TEST', 'isIndividual' => false],
        ]]]));

        $dto = $svc->getVehicleOwnerDTO('AAG', '1234567', '01A001AA');

        $this->assertSame('ORGANIZATION', $dto->ownerType);
        $this->assertSame('MCHJ TEST', $dto->name);
    }

    public function testVehicleNotFoundIsNotRetried()
    {
        $calls = [];
        $svc = new KafolatService($this->client(['/api/Osago/vehicle' => ['http' => 404, 'body' => [
            'success' => false, 'error' => ['code' => 404, 'message' => 'not found'], 'result' => null,
        ]]], $calls));

        $this->assertFalse($svc->getVehicleOwnerDTO('AAG', '1234567', '30W214PB')->success);
        $this->assertCount(1, $calls);
    }

    public function testTransportFailureIsRetriedThenFails()
    {
        $calls = [];
        $svc = new KafolatService($this->client(['/api/Osago/vehicle' => ['http' => 502, 'body' => []]], $calls));

        $this->assertFalse($svc->getVehicleOwnerDTO('AAG', '1234567', '30W214PB')->success);
        $this->assertCount(3, $calls);
    }

    public function testOwnerNeedsVehicleAndNormalizesBirthDate()
    {
        $svc = new KafolatService($this->client(['/api/Osago/vehicle/owner' => ['http' => 200, 'body' => [
            'success' => true,
            'result' => ['passportSeries' => 'AD', 'passportNumber' => '1234567', 'birthDate' => '1984-01-13T00:00:00', 'fullName' => 'KASIMOV FERUZ DJURAYEVICH'],
        ]]]));

        $this->assertFalse($svc->getPersonByPinflDTO('AD', '1234567', null)->success);

        $dto = $svc->getPersonByPinflDTO('AD', '1234567', ['govNumber' => '30W214PB', 'techSeria' => 'AAG', 'techNumber' => '1234567']);
        $this->assertTrue($dto->success);
        $this->assertSame('1984-01-13T00:00:00.000Z', $dto->birthDate);
        $this->assertSame('FERUZ', $dto->firstName);
        $this->assertNull($dto->pinfl);
    }

    public function testDriverFoundHasLicense()
    {
        $calls = [];
        $svc = new KafolatService($this->client(['/api/osago/driverInfo' => ['http' => 200, 'body' => [
            'success' => true,
            'result' => ['passportSeries' => 'AB', 'passportNumber' => '1234567', 'birthDate' => '1992-07-25T00:00:00', 'fullName' => 'ALIYEV VALI KARIM OGLI'],
        ]]], $calls));

        $dto = $svc->getPersonByBirthdateDTO('AB', '1234567', '1992-07-25T00:00:00.000Z');

        $this->assertTrue($dto->success);
        $this->assertTrue((bool)$dto->driverLicense);
        $this->assertSame('KARIM OGLI', $dto->middleName);
        $this->assertSame('1992-07-25', $calls[0][1]['birthDate']);
    }

    public function testDriverWithoutLicenseIsSuccessButNoLicense()
    {
        $svc = new KafolatService($this->client(['/api/osago/driverInfo' => ['http' => 404, 'body' => [
            'success' => false, 'error' => ['code' => 636, 'message' => 'Водительское удостоверение не найдено'], 'result' => null,
        ]]]));

        $dto = $svc->getPersonByBirthdateDTO('AB', '1234567', '1992-07-25T00:00:00.000Z');

        $this->assertTrue($dto->success);
        $this->assertFalse((bool)$dto->driverLicense);
    }

    public function testDriverNotFound()
    {
        $svc = new KafolatService($this->client(['/api/osago/driverInfo' => ['http' => 404, 'body' => [
            'success' => false, 'error' => ['code' => 404, 'message' => 'not found'], 'result' => null,
        ]]]));

        $this->assertFalse($svc->getPersonByBirthdateDTO('AB', '1234567', '1992-07-25T00:00:00.000Z')->success);
    }

    public function testCalculateMapsSeasonAndReturnsPremiumInTiyin()
    {
        $calls = [];
        $svc = new KafolatService($this->client(['/api/Osago/calculate' => ['http' => 200, 'body' => [
            'success' => true, 'result' => ['insurancePremium' => 560000],
        ]]], $calls));

        $season = (new SeasonalInsuranceCatalog())->byKey('1y');
        $dto = $svc->getCalculateOsagoDTO(
            ['govNumber' => '30W214PB', 'techSeria' => 'AAG', 'techNumber' => '1234567'],
            $season['id'],
            false,
            '2026-11-03'
        );

        $this->assertTrue($dto->success);
        $this->assertSame('56000000', $dto->premium); // 560 000 so'm = 56 000 000 tiyin
        $this->assertSame(7, $calls[0][1]['seasonalType']);
        $this->assertFalse($calls[0][1]['isLimited']);
    }

    public function testCalculateUnknownSeasonFails()
    {
        $svc = new KafolatService($this->client([]));

        $this->assertFalse($svc->getCalculateOsagoDTO(['govNumber' => 'A', 'techSeria' => 'B', 'techNumber' => 'C'], 'bad-id', false)->success);
    }

    public function testPremiumServiceRequiresVehicleForKafolat()
    {
        $svc = new PremiumCalculationService(null, new KafolatService($this->client([])), Setting::LOOKUP_KAFOLAT);

        $this->assertTrue($svc->isKafolat());
        $this->assertFalse($svc->calculate('x', false, null, null, [])->success);
    }

    public function testDriverLookupServiceRoutesToKafolat()
    {
        $svc = new KafolatService($this->client(['/api/osago/driverInfo' => ['http' => 200, 'body' => [
            'success' => true,
            'result' => ['passportSeries' => 'ZZ', 'passportNumber' => '7654321', 'birthDate' => '1990-05-01T00:00:00', 'fullName' => 'A B C'],
        ]]]));

        $dto = (new DriverLookupService(null, $svc, Setting::LOOKUP_KAFOLAT))->lookup('zz', '7654321', '1990-05-01T00:00:00.000Z');

        $this->assertTrue($dto->success);
        $this->assertSame('B', $dto->firstName);
    }
}
