<?php
namespace tests\codeception\api\models\base;

use api\models\base\KeyConfirmationForm;
use Codeception\Specify;
use common\models\EmailActivation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;

class KeyConfirmationFormTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testGetActivationCodeModel() {
        $model = new KeyConfirmationForm();
        $model->key = $this->tester->grabFixture('emailActivations', 'freshRegistrationConfirmation')['key'];
        $this->assertInstanceOf(EmailActivation::class, $model->getActivationCodeModel());

        $model = new KeyConfirmationForm();
        $model->key = 'this-is-invalid-key';
        $this->assertNull($model->getActivationCodeModel());
    }

}
