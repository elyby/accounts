<?php
namespace codeception\common\unit\validators;

use Codeception\Specify;
use common\validators\LanguageValidator;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\unit\TestCase;

class LanguageValidatorTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function testGetFilesNames() {
        $model = $this->createModelWithFixturePath();
        $this->assertEquals(['en', 'ru'], $this->callProtected($model, 'getFilesNames'));
    }

    public function testValidateValueSupportedLanguage() {
        $model = $this->createModelWithFixturePath();
        $this->assertNull($this->callProtected($model, 'validateValue', 'ru'));
    }

    public function testValidateNotSupportedLanguage() {
        $model = $this->createModelWithFixturePath();
        $this->assertEquals([$model->message, []], $this->callProtected($model, 'validateValue', 'by'));
    }

    /**
     * @return LanguageValidator
     */
    private function createModelWithFixturePath() {
        return new class extends LanguageValidator {
            public function getFolderPath() {
                return __DIR__ . '/../fixtures/data/i18n';
            }
        };
    }

}
