<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\models\authentication\LoginForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\tests\fixtures\AccountFixture;
use OTPHP\TOTP;

class LoginFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testValidateLogin(): void {
        $model = $this->createWithAccount(null);
        $model->login = 'mock-login';
        $model->validateLogin('login');
        $this->assertSame(['error.login_not_exist'], $model->getErrors('login'));

        $model = $this->createWithAccount(new Account());
        $model->login = 'mock-login';
        $model->validateLogin('login');
        $this->assertEmpty($model->getErrors('login'));
    }

    public function testValidatePassword(): void {
        $account = new Account();
        $account->password_hash = '$2y$04$N0q8DaHzlYILCnLYrpZfEeWKEqkPZzbawiS07GbSr/.xbRNweSLU6'; // 12345678
        $account->password_hash_strategy = Account::PASS_HASH_STRATEGY_YII2;

        $model = $this->createWithAccount($account);
        $model->password = '87654321';
        $model->validatePassword('password');
        $this->assertSame(['error.password_incorrect'], $model->getErrors('password'));

        $model = $this->createWithAccount($account);
        $model->password = '12345678';
        $model->validatePassword('password');
        $this->assertEmpty($model->getErrors('password'));
    }

    public function testValidateTotp(): void {
        $account = new Account(['password' => '12345678']);
        $account->password = '12345678';
        $account->is_otp_enabled = true;
        $account->otp_secret = 'AAAA';

        $model = $this->createWithAccount($account);
        $model->password = '12345678';
        $model->totp = '321123';
        $model->validateTotp('totp');
        $this->assertSame(['error.totp_incorrect'], $model->getErrors('totp'));

        $totp = TOTP::create($account->otp_secret);
        $model = $this->createWithAccount($account);
        $model->password = '12345678';
        $model->totp = $totp->now();
        $model->validateTotp('totp');
        $this->assertEmpty($model->getErrors('totp'));
    }

    public function testValidateActivity(): void {
        $account = new Account();
        $account->status = Account::STATUS_REGISTERED;
        $model = $this->createWithAccount($account);
        $model->validateActivity('login');
        $this->assertSame(['error.account_not_activated'], $model->getErrors('login'));

        $account = new Account();
        $account->status = Account::STATUS_BANNED;
        $model = $this->createWithAccount($account);
        $model->validateActivity('login');
        $this->assertSame(['error.account_banned'], $model->getErrors('login'));

        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $model = $this->createWithAccount($account);
        $model->validateActivity('login');
        $this->assertEmpty($model->getErrors('login'));
    }

    public function testLogin(): void {
        $account = new Account();
        $account->id = 1;
        $account->username = 'erickskrauch';
        $account->password_hash = '$2y$04$N0q8DaHzlYILCnLYrpZfEeWKEqkPZzbawiS07GbSr/.xbRNweSLU6'; // 12345678
        $account->password_hash_strategy = Account::PASS_HASH_STRATEGY_YII2;
        $account->status = Account::STATUS_ACTIVE;

        $model = $this->createWithAccount($account);
        $model->login = 'erickskrauch';
        $model->password = '12345678';

        $this->assertNotNull($model->login(), 'model should login user');
    }

    public function testLoginWithRehashing(): void {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'user-with-old-password-type');
        $model = $this->createWithAccount($account);
        $model->login = $account->username;
        $model->password = '12345678';

        $this->assertNotNull($model->login());
        $this->assertSame(Account::PASS_HASH_STRATEGY_YII2, $account->password_hash_strategy);
        $this->assertNotSame('133c00c463cbd3e491c28cb653ce4718', $account->password_hash);
    }

    private function createWithAccount(?Account $account): LoginForm {
        $model = $this->createPartialMock(LoginForm::class, ['getAccount']);
        $model->method('getAccount')->willReturn($account);

        return $model;
    }

}
