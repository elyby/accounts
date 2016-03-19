<?php
namespace tests\codeception\api\models\base;

use api\models\base\PasswordProtectedForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\TestCase;

class PasswordProtectedFormTest extends TestCase {
    use Specify;

    public function testValidatePassword() {
        $this->specify('error.password_invalid on passing invalid account password', function() {
            $model = new DummyBasePasswordProtectedForm();
            $model->password = 'some-invalid-password';
            $model->validatePassword();
            expect($model->getErrors('password'))->equals(['error.password_invalid']);
        });

        $this->specify('no errors on passing valid account password', function() {
            $model = new DummyBasePasswordProtectedForm();
            $model->password = 'password_0';
            $model->validatePassword();
            expect($model->getErrors('password'))->isEmpty();
        });
    }

}

class DummyBasePasswordProtectedForm extends PasswordProtectedForm {

    protected function getAccount() {
        return new Account([
            'password' => 'password_0',
        ]);
    }

}
