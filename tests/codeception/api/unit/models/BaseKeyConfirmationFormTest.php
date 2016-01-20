<?php
namespace tests\codeception\api\models;

use api\models\BaseKeyConfirmationForm;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property array $emailActivations
 */
class BaseKeyConfirmationFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    protected function createModel($key = null) {
        return new BaseKeyConfirmationForm([
            'key' => $key,
        ]);
    }

    public function testEmptyKey() {
        $model = $this->createModel();
        $this->specify('get error.key_is_required with validating empty key field', function () use ($model) {
            expect('model should don\'t pass validation', $model->validate())->false();
            expect('error messages should be set', $model->errors)->equals([
                'key' => [
                    'error.key_is_required',
                ],
            ]);
        });
    }

    public function testIncorrectKey() {
        $model = $this->createModel('not-exists-key');
        $this->specify('get error.key_not_exists with validation wrong key', function () use ($model) {
            expect('model should don\'t pass validation', $model->validate())->false();
            expect('error messages should be set', $model->errors)->equals([
                'key' => [
                    'error.key_not_exists',
                ],
            ]);
        });
    }

    public function testCorrectKey() {
        $model = $this->createModel($this->emailActivations[0]['key']);
        $this->specify('no errors if key exists', function () use ($model) {
            expect('model should pass validation', $model->validate())->true();
        });
    }

}
