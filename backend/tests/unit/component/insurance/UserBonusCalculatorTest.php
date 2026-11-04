<?php

namespace backend\tests\unit\component\insurance;

use backend\component\insurance\UserBonusCalculator;

class UserBonusCalculatorTest extends \Codeception\Test\Unit
{
    public function testRegularPlateUsesUserPercent()
    {
        $bonus = (new UserBonusCalculator(3, 5))->calculate(320000, '30W214PB');

        $this->assertSame(['percent' => 3, 'amount' => 9600], $bonus);
    }

    public function testTashkentPlatesUseTashkentPercent()
    {
        $calc = new UserBonusCalculator(3, 5);

        $this->assertSame(5, $calc->percentFor('01A123BC'));
        $this->assertSame(5, $calc->percentFor('10A123BC'));
    }

    public function testPlateIsNormalizedBeforeRegionCheck()
    {
        $this->assertSame(5, (new UserBonusCalculator(3, 5))->percentFor(' 01 a123bc '));
    }

    public function testEmptyPlateFallsBackToUserPercent()
    {
        $this->assertSame(3, (new UserBonusCalculator(3, 5))->percentFor(''));
    }

    public function testZeroPercentGivesZeroBonus()
    {
        $this->assertSame(['percent' => 0, 'amount' => 0], (new UserBonusCalculator(0, 5))->calculate(320000, '30W214PB'));
    }

    public function testAmountIsRoundedDown()
    {
        $this->assertSame(1, (new UserBonusCalculator(3, 5))->calculate(50, '30W214PB')['amount']);
    }
}
