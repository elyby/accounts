<?php
declare(strict_types=1);

namespace codeception\common\unit\validators;

use common\validators\LanguageValidator;
use tests\codeception\common\unit\TestCase;

/**
 * @covers \common\validators\LanguageValidator
 */
class LanguageValidatorTest extends TestCase {

    /**
     * @param string $locale
     * @param bool $shouldBeValid
     *
     * @dataProvider getTestCases
     */
    public function testValidate(string $locale, bool $shouldBeValid): void {
        $validator = new LanguageValidator();
        $result = $validator->validate($locale, $error);
        $this->assertSame($shouldBeValid, $result, $locale);
        if (!$shouldBeValid) {
            $this->assertSame($validator->message, $error);
        }
    }

    public function getTestCases(): array {
        return [
            // valid
            ['de', true],
            ['de_DE', true],
            ['deu', true],
            ['en', true],
            ['en_US', true],
            ['fil', true],
            ['fil_PH', true],
            ['zh', true],
            ['zh_Hans_CN', true],
            ['zh_Hant_HK', true],
            // invalid
            ['de_FR', false],
            ['fr_US', false],
            ['foo_bar', false],
            ['foo_bar_baz', false],
        ];
    }

}
