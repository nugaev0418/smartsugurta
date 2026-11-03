<?php

namespace backend\tests\unit\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\insurance\OwnerLookupService;
use backend\component\PersonByPinflDTO;

class OwnerLookupServiceTest extends \Codeception\Test\Unit
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
            public function getPersonByPinflDTO(string $seria, string $number, string $pinfl): PersonByPinflDTO
            {
                $this->lastArgs = [$seria, $number, $pinfl];
                return new PersonByPinflDTO(['success' => true]);
            }
        };

        (new OwnerLookupService($fake, null, \common\models\Setting::LOOKUP_EAI))->lookupByPinfl(' ad ', ' 6970989 ', ' 12345678901234 ');

        $this->assertSame(['AD', '6970989', '12345678901234'], $fake->lastArgs);
    }
}
