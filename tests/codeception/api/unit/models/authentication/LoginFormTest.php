<?php
namespace tests\codeception\api\models\authentication;

use api\components\User\LoginResult;
use api\models\AccountIdentity;
use api\models\authentication\LoginForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

/**
 * @property AccountFixture $accounts
 */
class LoginFormTest extends DbTestCase {
    use Specify;

    private $originalRemoteAddr;

    public function setUp() {
        $this->originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
        $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
    }

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testValidateLogin() {
        $this->specify('error.login_not_exist if login not exists', function () {
            $model = $this->createModel([
                'login' => 'mr-test',
                'account' => null,
            ]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $this->specify('no errors if login exists', function () {
            $model = $this->createModel([
                'login' => 'mr-test',
                'account' => new AccountIdentity(),
            ]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testValidatePassword() {
        $this->specify('error.password_incorrect if password invalid', function () {
            $model = $this->createModel([
                'password' => '87654321',
                'account' => new AccountIdentity(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            expect($model->getErrors('password'))->equals(['error.password_incorrect']);
        });

        $this->specify('no errors if password valid', function () {
            $model = $this->createModel([
                'password' => '12345678',
                'account' => new AccountIdentity(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            expect($model->getErrors('password'))->isEmpty();
        });
    }

    public function testValidateActivity() {
        $this->specify('error.account_not_activated if account in not activated state', function () {
            $model = $this->createModel([
                'account' => new AccountIdentity(['status' => Account::STATUS_REGISTERED]),
            ]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->equals(['error.account_not_activated']);
        });

        $this->specify('no errors if account active', function () {
            $model = $this->createModel([
                'account' => new AccountIdentity(['status' => Account::STATUS_ACTIVE]),
            ]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testLogin() {
        $this->specify('user should be able to login with correct username and password', function () {
            $model = $this->createModel([
                'login' => 'erickskrauch',
                'password' => '12345678',
                'account' => new AccountIdentity([
                    'username' => 'erickskrauch',
                    'password' => '12345678',
                    'status' => Account::STATUS_ACTIVE,
                ]),
            ]);
            expect('model should login user', $model->login())->isInstanceOf(LoginResult::class);
            expect('error message should not be set', $model->errors)->isEmpty();
        });
    }

    public function testLoginWithRehashing() {
        $this->specify('user, that login using account with old pass hash strategy should update it automatically', function () {
            $model = new LoginForm([
                'login' => $this->accounts['user-with-old-password-type']['username'],
                'password' => '12345678',
            ]);
            expect($model->login())->isInstanceOf(LoginResult::class);
            expect($model->errors)->isEmpty();
            expect($model->getAccount()->password_hash_strategy)->equals(Account::PASS_HASH_STRATEGY_YII2);
        });
    }

    /**
     * @param array $params
     * @return LoginForm
     */
    private function createModel(array $params = []) {
        return new class($params) extends LoginForm {
            private $_account;

            public function setAccount($value) {
                $this->_account = $value;
            }

            public function getAccount() {
                return $this->_account;
            }
        };
    }

}
