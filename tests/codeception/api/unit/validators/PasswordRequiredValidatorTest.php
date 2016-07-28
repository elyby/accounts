<?php
namespace codeception\api\unit\validators;

use api\validators\PasswordRequiredValidator;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\_support\ProtectedCaller;
use common\helpers\Error as E;

class PasswordRequiredValidatorTest extends DbTestCase {
    use Specify;
    use ProtectedCaller;

    public function testValidateValue() {
        $account = new Account(['password' => '12345678']);
        $this->specify('get error.password_required if password is empty', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', ''))->equals([E::PASSWORD_REQUIRED, []]);
        });

        $this->specify('get error.password_incorrect if password is incorrect', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', '87654321'))->equals([E::PASSWORD_INCORRECT, []]);
        });

        $this->specify('no errors, if password is correct for provided account', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', '12345678'))->null();
        });
    }

}
