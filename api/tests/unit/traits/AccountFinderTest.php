<?php
namespace api\tests\_support\traits;

use api\tests\unit\TestCase;
use api\traits\AccountFinder;
use Codeception\Specify;
use common\models\Account;
use common\tests\fixtures\AccountFixture;

class AccountFinderTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testGetAccount() {
        $model = new AccountFinderTestTestClass();
        /** @var Account $account */
        $accountFixture = $this->tester->grabFixture('accounts', 'admin');
        $model->login = $accountFixture->email;
        $account = $model->getAccount();
        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame($accountFixture->id, $account->id, 'founded account for passed login data');

        $model = new AccountFinderTestTestClass();
        $model->login = 'unexpected';
        $this->assertNull($account = $model->getAccount(), 'null, if account can\'t be found');
    }

    public function testGetLoginAttribute() {
        $model = new AccountFinderTestTestClass();
        $model->login = 'erickskrauch@ely.by';
        $this->assertEquals('email', $model->getLoginAttribute(), 'if login look like email value, then \'email\'');

        $model = new AccountFinderTestTestClass();
        $model->login = 'erickskrauch';
        $this->assertEquals('username', $model->getLoginAttribute(), 'username in any other case');
    }

}

class AccountFinderTestTestClass {
    use AccountFinder;

    public $login;

    public function getLogin(): string {
        return $this->login;
    }

}
