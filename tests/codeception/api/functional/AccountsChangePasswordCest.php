<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangePasswordCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function _after() {
        /** @var Account $account */
        $account = Account::findOne(1);
        $account->setPassword('password_0');
        $account->save();
    }

    public function testChangePassword(FunctionalTester $I) {
        $I->wantTo('change my password');
        $I->amAuthenticated();

        $this->route->changePassword('password_0', 'new-password', 'new-password');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->notLoggedIn();

        $loginRoute = new AuthenticationRoute($I);
        $loginRoute->login('Admin', 'new-password');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
