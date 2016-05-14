<?php
namespace tests\codeception\api\models\base;

use api\models\base\KeyConfirmationForm;
use Codeception\Specify;
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
            'emailActivations' => EmailActivationFixture::class,
        ];
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
