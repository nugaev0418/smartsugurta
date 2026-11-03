<?php

namespace backend\tests\unit\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\insurance\VehicleLookupService;
use backend\component\VehicleOwnerDTO;

class VehicleLookupServiceTest extends \Codeception\Test\Unit
{
    // Servis natijani FileCache'da saqlaydi; testlar bir-biriga ta'sir qilmasligi uchun tozalaymiz.
    protected function _before()
    {
        \Yii::$app->cache->flush();
    }

    public function testNormalizesInputBeforeCallingEuroAsia()
    {
        $fake = new class extends EuroAsiaService {
            public array $lastArgs = [];
            public function __construct() {}
            public function getVehicleOwnerDTO(string $techSeria, string $techNumber, string $licenseNumber): VehicleOwnerDTO
            {
                $this->lastArgs = [$techSeria, $techNumber, $licenseNumber];
                return new VehicleOwnerDTO(['success' => true]);
            }
        };

        (new VehicleLookupService($fake, null, \common\models\Setting::LOOKUP_EAI))->lookup(' aaf ', ' 2998242 ', '40011 vba');

        $this->assertSame(['AAF', '2998242', '40011VBA'], $fake->lastArgs);
    }

    public function testReturnsTheDtoUnchanged()
    {
        $fake = new class extends EuroAsiaService {
            public function __construct() {}
            public function getVehicleOwnerDTO(string $techSeria, string $techNumber, string $licenseNumber): VehicleOwnerDTO
            {
                return new VehicleOwnerDTO(['success' => false, 'ownerType' => 'PERSON']);
            }
        };

        $dto = (new VehicleLookupService($fake, null, \common\models\Setting::LOOKUP_EAI))->lookup('AAF', '2998242', '40011VBA');

        $this->assertFalse($dto->success);
        $this->assertSame('PERSON', $dto->ownerType);
    }
}
