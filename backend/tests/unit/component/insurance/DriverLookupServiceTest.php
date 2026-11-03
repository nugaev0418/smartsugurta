<?php

namespace backend\tests\unit\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\insurance\DriverLookupService;
use backend\component\PersonByBirthdateDTO;

class DriverLookupServiceTest extends \Codeception\Test\Unit
{
    // Servis natijani FileCache'da saqlaydi; testlar bir-biriga ta'sir qilmasligi uchun tozalaymiz.
    protected function _before()
    {
        \Yii::$app->cache->flush();
    }

    public function testNormalizesSeriaAndNumberButPassesBirthdateIsoThrough()
    {
        $fake = new class extends EuroAsiaService {
            public array $lastArgs = [];
            public function __construct() {}
            public function getPersonByBirthdateDTO(string $seria, string $number, string $birthdate): PersonByBirthdateDTO
            {
                $this->lastArgs = [$seria, $number, $birthdate];
                return new PersonByBirthdateDTO(['success' => true, 'driverLicense' => 'AB1234567']);
            }
        };

        (new DriverLookupService($fake, null, \common\models\Setting::LOOKUP_EAI))->lookup(' ab ', ' 4112696 ', '1992-07-25T00:00:00.000Z');

        $this->assertSame(['AB', '4112696', '1992-07-25T00:00:00.000Z'], $fake->lastArgs);
    }

    public function testReturnsDtoWithSuccessAndLicenseFlagsIntact()
    {
        $fake = new class extends EuroAsiaService {
            public function __construct() {}
            public function getPersonByBirthdateDTO(string $seria, string $number, string $birthdate): PersonByBirthdateDTO
            {
                return new PersonByBirthdateDTO(['success' => true, 'driverLicense' => null]);
            }
        };

        $dto = (new DriverLookupService($fake, null, \common\models\Setting::LOOKUP_EAI))->lookup('AB', '4112696', '1992-07-25T00:00:00.000Z');

        $this->assertTrue($dto->success);
        $this->assertFalse((bool) $dto->driverLicense);
    }
}
