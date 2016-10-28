<?php
namespace tests\codeception\api\traits;

use api\models\AccountIdentity;
use api\traits\AccountFinder;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;

class AccountFinderTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testGetAccount() {
        $this->specify('founded account for passed login data', function() {
            $model = new AccountFinderTestTestClass();
            /** @var Account $account */
            $account = $this->tester->grabFixture('accounts', 'admin');
            $model->login = $account->email;
            $account = $model->getAccount();
            expect($account)->isInstanceOf(Account::class);
            expect($account->id)->equals($account->id);
        });

        $this->specify('founded account for passed login data with changed account model class name', function() {
            /** @var AccountFinderTestTestClass $model */
            $model = new class extends AccountFinderTestTestClass {
                protected function getAccountClassName() {
                    return AccountIdentity::class;
                }
            };
            /** @var Account $account */
            $account = $this->tester->grabFixture('accounts', 'admin');
            $model->login = $account->email;
            $account = $model->getAccount();
            expect($account)->isInstanceOf(AccountIdentity::class);
            expect($account->id)->equals($account->id);
        });

        $this->specify('null, if account not founded', function() {
            $model = new AccountFinderTestTestClass();
            $model->login = 'unexpected';
            expect($account = $model->getAccount())->null();
        });
    }

    public function testGetLoginAttribute() {
        $this->specify('if login look like email value, then \'email\'', function() {
            $model = new AccountFinderTestTestClass();
            $model->login = 'erickskrauch@ely.by';
            expect($model->getLoginAttribute())->equals('email');
        });

        $this->specify('username in any other case', function() {
            $model = new AccountFinderTestTestClass();
            $model->login = 'erickskrauch';
            expect($model->getLoginAttribute())->equals('username');
        });
    }

}

class AccountFinderTestTestClass {
    use AccountFinder;

    public $login;

    public function getLogin() {
        return $this->login;
    }

}
