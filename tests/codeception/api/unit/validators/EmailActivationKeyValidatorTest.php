<?php
namespace codeception\api\unit\validators;

use api\validators\EmailActivationKeyValidator;
use Codeception\Specify;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\EmailActivationFixture;

/**
 * @property EmailActivationFixture $emailActivations
 */
class EmailActivationKeyValidatorTest extends DbTestCase {
    use Specify;
    use ProtectedCaller;

    public function fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testFindEmailActivationModel() {
        $this->specify('get EmailActivation model for exists key', function() {
            $key = array_values($this->emailActivations->data)[0]['key'];
            $model = new EmailActivationKeyValidator();
            /** @var EmailActivation $result */
            $result = $this->callProtected($model, 'findEmailActivationModel', $key);
            expect($result)->isInstanceOf(EmailActivation::class);
            expect($result->key)->equals($key);
        });

        $this->specify('get null model for exists key', function() {
            $model = new EmailActivationKeyValidator();
            expect($this->callProtected($model, 'findEmailActivationModel', 'invalid-key'))->null();
        });
    }

    public function testValidateValue() {
        $this->specify('get error.key_not_exists with validation wrong key', function () {
            /** @var EmailActivationKeyValidator $model */
            $model = new class extends EmailActivationKeyValidator {
                public function findEmailActivationModel($key) {
                    return null;
                }
            };
            expect($this->callProtected($model, 'validateValue', null))->equals([$model->notExist, []]);
        });

        $this->specify('get error.key_expire if we use old key', function () {
            /** @var EmailActivationKeyValidator $model */
            $model = new class extends EmailActivationKeyValidator {
                public function findEmailActivationModel($key) {
                    $codeModel = new ForgotPassword();
                    $codeModel->created_at = time() - $codeModel->expirationTimeout - 10;

                    return $codeModel;
                }
            };
            expect($this->callProtected($model, 'validateValue', null))->equals([$model->expired, []]);
        });

        $this->specify('no errors, if model exists and not expired', function () {
            /** @var EmailActivationKeyValidator $model */
            $model = new class extends EmailActivationKeyValidator {
                public function findEmailActivationModel($key) {
                    return new EmailActivation();
                }
            };
            expect($this->callProtected($model, 'validateValue', null))->null();
        });
    }

}
