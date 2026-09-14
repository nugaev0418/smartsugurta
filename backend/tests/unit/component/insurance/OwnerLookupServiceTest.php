<?php

namespace backend\tests\unit\component\insurance;

use backend\component\EuroAsiaService;
use backend\component\insurance\OwnerLookupService;
use backend\component\PersonByPinflDTO;

class OwnerLookupServiceTest extends \Codeception\Test\Unit
{
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

        (new OwnerLookupService($fake))->lookupByPinfl(' ad ', ' 6970989 ', ' 12345678901234 ');

        $this->assertSame(['AD', '6970989', '12345678901234'], $fake->lastArgs);
    }
}
