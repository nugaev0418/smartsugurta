<?php

namespace backend\tests\unit\component\insurance;

use backend\component\insurance\SeasonalInsuranceCatalog;

/**
 * Guards the hardcoded GUID/days/period_type values against accidental
 * changes — these come from EuroAsia and Gross respectively and are not
 * derivable from anything else in the codebase.
 */
class SeasonalInsuranceCatalogTest extends \Codeception\Test\Unit
{
    private SeasonalInsuranceCatalog $catalog;

    protected function _before()
    {
        $this->catalog = new SeasonalInsuranceCatalog();
    }

    public function testOneYear()
    {
        $this->assertSame(
            ['id' => '8465a831-850f-4445-a995-ef71195094ab', 'days' => 365, 'period_type' => 7],
            $this->catalog->byKey('1y')
        );
    }

    public function testSixMonths()
    {
        $this->assertSame(
            ['id' => '9848096e-cc12-4dbd-893b-41f2cdfc9a0e', 'days' => 180, 'period_type' => 1],
            $this->catalog->byKey('6m')
        );
    }

    public function testTwentyDays()
    {
        $this->assertSame(
            ['id' => '0d546748-0ba6-43bc-9ce2-1b977ad9e494', 'days' => 20, 'period_type' => 8],
            $this->catalog->byKey('20d')
        );
    }

    public function testUnknownKeyReturnsNull()
    {
        $this->assertNull($this->catalog->byKey('bogus'));
    }

    public function testKeysListsAllThree()
    {
        $this->assertSame(['1y', '6m', '20d'], $this->catalog->keys());
    }
}
