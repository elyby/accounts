<?php
namespace api\tests\_support\models\authentication;

use api\components\User\AuthenticationResult;
use api\models\authentication\LoginForm;
use api\tests\unit\TestCase;
use Codeception\Specify;
use common\models\Account;
use common\tests\fixtures\AccountFixture;
use OTPHP\TOTP;

class LoginFormTest extends TestCase {
    use Specify;

    private $originalRemoteAddr;

    protected function setUp() {
        $this->originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        parent::setUp();
    }

    protected function tearDown() {
        parent::tearDown();
        $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
    }

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testValidateLogin() {
        $this->specify('error.login_not_exist if login not exists', function() {
            $model = $this->createModel([
                'login' => 'mr-test',
                'account' => null,
            ]);
            $model->validateLogin('login');
            $this->assertSame(['error.login_not_exist'], $model->getErrors('login'));
        });

        $this->specify('no errors if login exists', function() {
            $model = $this->createModel([
                'login' => 'mr-test',
                'account' => new Account(),
            ]);
            $model->validateLogin('login');
            $this->assertEmpty($model->getErrors('login'));
        });
    }

    public function testValidatePassword() {
        $this->specify('error.password_incorrect if password invalid', function() {
            $model = $this->createModel([
                'password' => '87654321',
                'account' => new Account(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            $this->assertSame(['error.password_incorrect'], $model->getErrors('password'));
        });

        $this->specify('no errors if password valid', function() {
            $model = $this->createModel([
                'password' => '12345678',
                'account' => new Account(['password' => '12345678']),
            ]);
            $model->validatePassword('password');
            $this->assertEmpty($model->getErrors('password'));
        });
    }

    public function testValidateTotp() {
        $account = new Account(['password' => '12345678']);
        $account->password = '12345678';
        $account->is_otp_enabled = true;
        $account->otp_secret = 'AAAA';

        $this->specify('error.totp_incorrect if totp invalid', function() use ($account) {
            $model = $this->createModel([
                'password' => '12345678',
                'totp' => '321123',
                'account' => $account,
            ]);
            $model->validateTotp('totp');
            $this->assertSame(['error.totp_incorrect'], $model->getErrors('totp'));
        });

        $totp = TOTP::create($account->otp_secret);
        $this->specify('no errors if password valid', function() use ($account, $totp) {
            $model = $this->createModel([
                'password' => '12345678',
                'totp' => $totp->now(),
                'account' => $account,
            ]);
            $model->validateTotp('totp');
            $this->assertEmpty($model->getErrors('totp'));
        });
    }

    public function testValidateActivity() {
        $this->specify('error.account_not_activated if account in not activated state', function() {
            $model = $this->createModel([
                'account' => new Account(['status' => Account::STATUS_REGISTERED]),
            ]);
            $model->validateActivity('login');
            $this->assertSame(['error.account_not_activated'], $model->getErrors('login'));
        });

        $this->specify('error.account_banned if account has banned status', function() {
            $model = $this->createModel([
                'account' => new Account(['status' => Account::STATUS_BANNED]),
            ]);
            $model->validateActivity('login');
            $this->assertSame(['error.account_banned'], $model->getErrors('login'));
        });

        $this->specify('no errors if account active', function() {
            $model = $this->createModel([
                'account' => new Account(['status' => Account::STATUS_ACTIVE]),
            ]);
            $model->validateActivity('login');
            $this->assertEmpty($model->getErrors('login'));
        });
    }

    public function testLogin() {
        $model = $this->createModel([
            'login' => 'erickskrauch',
            'password' => '12345678',
            'account' => new Account([
                'username' => 'erickskrauch',
                'password' => '12345678',
                'status' => Account::STATUS_ACTIVE,
            ]),
        ]);
        $this->assertInstanceOf(AuthenticationResult::class, $model->login(), 'model should login user');
        $this->assertEmpty($model->getErrors(), 'error message should not be set');
    }

    public function testLoginWithRehashing() {
        $model = new LoginForm([
            'login' => $this->tester->grabFixture('accounts', 'user-with-old-password-type')['username'],
            'password' => '12345678',
        ]);
        $this->assertInstanceOf(AuthenticationResult::class, $model->login());
        $this->assertEmpty($model->getErrors());
        $this->assertSame(
            Account::PASS_HASH_STRATEGY_YII2,
            $model->getAccount()->password_hash_strategy,
            'user, that login using account with old pass hash strategy should update it automatically'
        );
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

            public function getAccount(): ?Account {
                return $this->_account;
            }
        };
    }

}
