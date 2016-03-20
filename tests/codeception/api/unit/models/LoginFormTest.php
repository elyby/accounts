<?php
namespace tests\codeception\api\models;

use api\models\LoginForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use Yii;

class LoginFormTest extends DbTestCase {
    use Specify;

    public function testValidateLogin() {
        $this->specify('error.login_not_exist if login not exists', function() {
            $model = new DummyLoginForm([
                'login' => 'mr-test',
                'account' => null,
            ]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $this->specify('no errors if login exists', function() {
            $model = new DummyLoginForm([
                'login' => 'mr-test',
                'account' => new Account(),
            ]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testValidatePassword() {
        $this->specify('error.password_incorrect if password invalid', function() {
            $model = new DummyLoginForm([
                'password' => '87654321',
                'account' => new Account(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            expect($model->getErrors('password'))->equals(['error.password_incorrect']);
        });

        $this->specify('no errors if password valid', function() {
            $model = new DummyLoginForm([
                'password' => '12345678',
                'account' => new Account(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            expect($model->getErrors('password'))->isEmpty();
        });
    }

    public function testValidateActivity() {
        $this->specify('error.account_not_activated if account in not activated state', function() {
            $model = new DummyLoginForm([
                'account' => new Account(['status' => Account::STATUS_REGISTERED]),
            ]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->equals(['error.account_not_activated']);
        });

        $this->specify('no errors if account active', function() {
            $model = new DummyLoginForm([
                'account' => new Account(['status' => Account::STATUS_ACTIVE]),
            ]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testLogin() {
        $this->specify('user should be able to login with correct username and password', function () {
            $model = new DummyLoginForm([
                'login' => 'erickskrauch',
                'password' => '12345678',
                'account' => new Account([
                    'username' => 'erickskrauch',
                    'password' => '12345678',
                    'status' => Account::STATUS_ACTIVE,
                ]),
            ]);
            expect('model should login user', $model->login())->notEquals(false);
            expect('error message should not be set', $model->errors)->isEmpty();
        });
    }

    public function testGetLoginAttribute() {
        $this->specify('email if login look like email value', function() {
            $model = new DummyLoginForm(['login' => 'erickskrauch@ely.by']);
            expect($model->getLoginAttribute())->equals('email');
        });

        $this->specify('username in any other case', function() {
            $model = new DummyLoginForm(['login' => 'erickskrauch']);
            expect($model->getLoginAttribute())->equals('username');
        });
    }

}

class DummyLoginForm extends LoginForm {

    private $_account;

    public function setAccount($value) {
        $this->_account = $value;
    }

    public function getAccount() {
        return $this->_account;
    }

}
