<?php
namespace codeception\api\unit\validators;

use api\validators\PasswordRequiredValidator;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\_support\ProtectedCaller;

class PasswordRequiredValidatorTest extends DbTestCase {
    use Specify;
    use ProtectedCaller;

    public function testValidateValue() {
        $account = new Account(['password' => '12345678']);
        $this->specify('get error.password_required if password is empty', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', ''))->equals(['error.password_required', []]);
        });

        $this->specify('get error.password_invalid if password is incorrect', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', '87654321'))->equals(['error.password_invalid', []]);
        });

        $this->specify('no errors, if password is correct for provided account', function () use ($account) {
            $model = new PasswordRequiredValidator(['account' => $account]);
            expect($this->callProtected($model, 'validateValue', '12345678'))->null();
        });
    }

}
