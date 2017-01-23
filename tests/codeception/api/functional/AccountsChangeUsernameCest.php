<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangeUsernameCest {

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
        $account->username = 'Admin';
        $account->save();
    }

    public function testChangeUsername(FunctionalTester $I) {
        $I->wantTo('change my nickname');
        $I->amAuthenticated();

        $this->route->changeUsername('password_0', 'bruce_wayne');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testChangeUsernameNotAvailable(FunctionalTester $I) {
        $I->wantTo('see, that nickname "in use" is not available');
        $I->amAuthenticated();

        $this->route->changeUsername('password_0', 'Jon');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'username' => 'error.username_not_available',
            ],
        ]);
    }

}
