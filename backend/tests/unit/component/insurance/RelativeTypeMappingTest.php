<?php

namespace backend\tests\unit\component\insurance;

use backend\component\bot\stage\DriverStageHandler;
use backend\component\RelativeType;
use backend\controllers\WebAppController;
use ReflectionClass;

/**
 * BotController's chat flow (DriverStageHandler::RELATIVE_CODE_BY_KEYWORD,
 * keyed on Text-model keywords) and WebAppController's Mini App flow
 * (RELATIVE_TYPES, keyed on literal Uzbek labels) map two different input
 * domains to the same 0-10 RelativeType codes, so they were deliberately
 * NOT merged into one table (see CLAUDE.md / plan). This test is the
 * substitute safety net: both maps must still cover every RelativeType
 * code, so a new relation added to one is never silently missing from the
 * other.
 */
class RelativeTypeMappingTest extends \Codeception\Test\Unit
{
    private function allRelativeTypeCodes(): array
    {
        // Public constants only (NOT_RELATED..YOUNGER_SISTER, values 0-10) —
        // excludes the private EAI_IDS lookup array, which getConstants()
        // without a filter would otherwise include as an extra "code".
        $ref = new ReflectionClass(RelativeType::class);
        return array_values($ref->getConstants(\ReflectionClassConstant::IS_PUBLIC));
    }

    public function testBotKeywordMapCoversEveryRelativeTypeCode()
    {
        $ref = new ReflectionClass(DriverStageHandler::class);
        $map = $ref->getConstant('RELATIVE_CODE_BY_KEYWORD');

        $this->assertNotFalse($map, 'DriverStageHandler::RELATIVE_CODE_BY_KEYWORD not found');

        $codes = array_values($map);
        sort($codes);
        $expected = $this->allRelativeTypeCodes();
        sort($expected);

        $this->assertSame($expected, $codes);
    }

    public function testWebAppLabelMapCoversEveryRelativeTypeCode()
    {
        $ref = new ReflectionClass(WebAppController::class);
        $map = $ref->getConstant('RELATIVE_TYPES');

        $this->assertNotFalse($map, 'WebAppController::RELATIVE_TYPES not found');

        $codes = array_values($map);
        sort($codes);
        $expected = $this->allRelativeTypeCodes();
        sort($expected);

        $this->assertSame($expected, $codes);
    }
}
