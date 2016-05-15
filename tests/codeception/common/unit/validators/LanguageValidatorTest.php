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
        $this->specify('get list of 2 languages: ru and en', function() {
            $model = $this->createModelWithFixturePath();
            expect($this->callProtected($model, 'getFilesNames'))->equals(['en', 'ru']);
        });
    }

    public function testValidateValue() {
        $this->specify('get null, because language is supported', function() {
            $model = $this->createModelWithFixturePath();
            expect($this->callProtected($model, 'validateValue', 'ru'))->null();
        });

        $this->specify('get error message, because language is unsupported', function() {
            $model = $this->createModelWithFixturePath();
            expect($this->callProtected($model, 'validateValue', 'by'))->equals([
                $model->message,
                [],
            ]);
        });
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
