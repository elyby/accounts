<?php
namespace tests\codeception\api\models\base;

use api\models\base\KeyConfirmationForm;
use Codeception\Specify;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property EmailActivationFixture $emailActivations
 */
class KeyConfirmationFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    public function testValidateKey() {
        $this->specify('get error.key_not_exists with validation wrong key', function () {
            /** @var KeyConfirmationForm $model */
            $model = new class extends KeyConfirmationForm {
                public function getActivationCodeModel() {
                    return null;
                }
            };
            $model->validateKey('key');
            expect($model->errors)->equals([
                'key' => [
                    'error.key_not_exists',
                ],
            ]);
        });

        $this->specify('no errors, if model exists', function () {
            /** @var KeyConfirmationForm $model */
            $model = new class extends KeyConfirmationForm {
                public function getActivationCodeModel() {
                    return new EmailActivation();
                }
            };
            $model->validateKey('key');
            expect($model->errors)->isEmpty();
        });
    }

    public function testValidateKeyExpiration() {
        $this->specify('get error.key_expire if we use old key', function () {
            /** @var KeyConfirmationForm $model */
            $model = new class extends KeyConfirmationForm {
                public function getActivationCodeModel() {
                    $codeModel = new ForgotPassword();
                    $codeModel->created_at = time() - $codeModel->expirationTimeout - 10;

                    return $codeModel;
                }
            };
            $model->validateKeyExpiration('key');
            expect($model->errors)->equals([
                'key' => [
                    'error.key_expire',
                ],
            ]);
        });

        $this->specify('no errors if key is not yet expired', function () {
            /** @var KeyConfirmationForm $model */
            $model = new class extends KeyConfirmationForm {
                public function getActivationCodeModel() {
                    $codeModel = new ForgotPassword();
                    $codeModel->created_at = time() - $codeModel->expirationTimeout + 10;

                    return $codeModel;
                }
            };
            $model->validateKeyExpiration('key');
            expect($model->errors)->isEmpty();
        });
    }

    public function testGetActivationCodeModel() {
        $this->specify('should return model, based on passed key', function() {
            $model = new KeyConfirmationForm();
            $model->key = array_values($this->emailActivations->data)[0]['key'];
            expect($model->getActivationCodeModel())->isInstanceOf(EmailActivation::class);
        });

        $this->specify('should return null, if passed key is invalid', function() {
            $model = new KeyConfirmationForm();
            $model->key = 'this-is-invalid-key';
            expect($model->getActivationCodeModel())->null();
        });
    }

}
