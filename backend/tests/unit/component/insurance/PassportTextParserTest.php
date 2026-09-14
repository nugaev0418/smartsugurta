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

    public function testLooksLikePersonPassportAcceptsValidSplitFields()
    {
        $this->assertTrue($this->parser->looksLikePersonPassport('AD', '6970989'));
        $this->assertTrue($this->parser->looksLikePersonPassport(' ad ', ' 6970989 '));
    }

    public function testLooksLikePersonPassportRejectsWrongLengthOrNonDigits()
    {
        $this->assertFalse($this->parser->looksLikePersonPassport('ADD', '6970989'));
        $this->assertFalse($this->parser->looksLikePersonPassport('AD', '697098'));
        $this->assertFalse($this->parser->looksLikePersonPassport('AD', '69709899'));
        $this->assertFalse($this->parser->looksLikePersonPassport('AD', '697098X'));
        $this->assertFalse($this->parser->looksLikePersonPassport('A1', '6970989'));
        $this->assertFalse($this->parser->looksLikePersonPassport('', ''));
    }

    public function testLooksLikeTechPassportAcceptsValidSplitFields()
    {
        $this->assertTrue($this->parser->looksLikeTechPassport('AAF', '2998242'));
        $this->assertTrue($this->parser->looksLikeTechPassport(' aaf ', ' 2998242 '));
    }

    public function testLooksLikeTechPassportRejectsWrongLengthOrNonDigits()
    {
        $this->assertFalse($this->parser->looksLikeTechPassport('AA', '2998242'));
        $this->assertFalse($this->parser->looksLikeTechPassport('AAFF', '2998242'));
        $this->assertFalse($this->parser->looksLikeTechPassport('AAF', '299824'));
        $this->assertFalse($this->parser->looksLikeTechPassport('AAF', '29982422'));
        $this->assertFalse($this->parser->looksLikeTechPassport('AAF', '299824X'));
    }
}
