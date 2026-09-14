<?php

namespace backend\tests\unit\component\insurance;

use backend\component\insurance\PassportTextParser;

class PassportTextParserTest extends \Codeception\Test\Unit
{
    private PassportTextParser $parser;

    protected function _before()
    {
        $this->parser = new PassportTextParser();
    }

    public function testLooksLikePassportAcceptsLowercaseAndWhitespace()
    {
        $this->assertTrue($this->parser->looksLikePassport('ad6970989'));
        $this->assertTrue($this->parser->looksLikePassport('  AD6970989  '));
    }

    public function testLooksLikePassportRejectsInternalSpaceOrBadShape()
    {
        $this->assertFalse($this->parser->looksLikePassport('AD 6970989'));
        $this->assertFalse($this->parser->looksLikePassport('123456789'));
        $this->assertFalse($this->parser->looksLikePassport('AD69709'));
    }

    public function testSplitPassport()
    {
        $this->assertSame(['seria' => 'AD', 'number' => '6970989'], $this->parser->splitPassport('AD6970989'));
    }

    public function testParseExtractsSeriaNumberAndBirthDate()
    {
        $result = $this->parser->parse('AB4112696 25.07.1992');

        $this->assertTrue($result['success']);
        $this->assertSame('AB', $result['series']);
        $this->assertSame('4112696', $result['number']);
        $this->assertSame('25.07.1992', $result['birth']);
    }

    public function testParseAcceptsCompactEightDigitDate()
    {
        $result = $this->parser->parse('ab4112696 25071992');

        $this->assertTrue($result['success']);
        $this->assertSame('25.07.1992', $result['birth']);
    }

    public function testParseFailsOnUnrecognizedText()
    {
        $result = $this->parser->parse('not a passport at all');

        $this->assertFalse($result['success']);
    }
}
