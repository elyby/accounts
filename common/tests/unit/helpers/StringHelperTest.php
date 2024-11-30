<?php
namespace common\tests\unit\helpers;

use common\helpers\StringHelper;
use common\tests\unit\TestCase;

class StringHelperTest extends TestCase {

    public function testGetEmailMask() {
        $this->assertSame('**@ely.by', StringHelper::getEmailMask('e@ely.by'));
        $this->assertSame('e**@ely.by', StringHelper::getEmailMask('es@ely.by'));
        $this->assertSame('e**i@ely.by', StringHelper::getEmailMask('eri@ely.by'));
        $this->assertSame('er**ch@ely.by', StringHelper::getEmailMask('erickskrauch@ely.by'));
        $this->assertSame('эр**уч@елу.бел', StringHelper::getEmailMask('эрикскрауч@елу.бел'));
    }

    public function testIsUuid() {
        $this->assertTrue(StringHelper::isUuid('a80b4487-a5c6-45a5-9829-373b4a494135'));
        $this->assertTrue(StringHelper::isUuid('a80b4487a5c645a59829373b4a494135'));
        $this->assertFalse(StringHelper::isUuid('12345678'));
    }

    /**
     * @dataProvider trimProvider
     */
    public function testTrim($expected, $string) {
        $result = StringHelper::trim($string);
        $this->assertSame($expected, $result);
    }

    /**
     * http://jkorpela.fi/chars/spaces.html
     * http://www.alanwood.net/unicode/general_punctuation.html
     *
     * @return array
     */
    public static function trimProvider(): array {
        return [
            ['foo   bar', '  foo   bar  '], // Simple spaces
            ['foo bar', ' foo bar'], // Only left side space
            ['foo bar', 'foo bar '], // Only right side space
            ['foo bar', "\n\t foo bar \n\t"], // New line, tab character and simple space
            ['fòô   bàř', '  fòô   bàř  '], // UTF-8 text
            ['fòô bàř', ' fòô bàř'], // Only left side space width UTF-8 text
            ['fòô bàř', 'fòô bàř '], // Only right side space width UTF-8 text
            ['fòô bàř', "\n\t fòô bàř \n\t"], // New line, tab character and simple space width UTF-8 string
            ['fòô', "\u{00a0}fòô\u{00a0}"], // No-break space (U+00A0)
            ['fòô', "\u{1680}fòô\u{1680}"], // Ogham space mark (U+1680)
            ['fòô', "\u{180e}fòô\u{180e}"], // Mongolian vowel separator (U+180E)
            ['fòô', "\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}fòô"], // Spaces U+2000 to U+2007
            ['fòô', "\u{2008}\u{2009}\u{200a}\u{200b}\u{200c}\u{200d}\u{200e}\u{200f}fòô"], // Spaces U+2008 to U+200F
            ['fòô', "\u{2028}\u{2029}\u{202a}\u{202b}\u{202c}\u{202d}\u{202e}\u{202f}fòô"], // Spaces U+2028 to U+202F
            ['fòô', "\u{2060}\u{2061}\u{2062}\u{2063}\u{2064}\u{2065}\u{2066}\u{2067}fòô"], // Spaces U+2060 to U+2067
            ['fòô', "\u{2068}\u{2069}\u{206a}\u{206b}\u{206c}\u{206d}\u{206e}\u{206f}fòô"], // Spaces U+2068 to U+206F
            ['fòô', "\u{205f}fòô\u{205f}"], // Medium mathematical space (U+205F)
            ['fòô', "\u{3000}fòô\u{3000}"], // Ideographic space (U+3000)
            ['fòô', "\u{feff}fòô\u{feff}"], // Zero width no-break space (U+FEFF)
        ];
    }

}
