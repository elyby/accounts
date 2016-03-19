<?php
namespace tests\codeception\api\functional;

use Codeception\Scenario;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\_pages\LoginRoute;
use tests\codeception\api\functional\_steps\AccountSteps;
use tests\codeception\api\FunctionalTester;

class AccountsChangeUsernameCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function _after(FunctionalTester $I) {
        /** @var Account $account */
        $account = Account::findOne(1);
        $account->username = 'Admin';
        $account->save();
    }

    public function testChangeUsername(FunctionalTester $I, Scenario $scenario) {
        $I->wantTo('change my password');
        $I = new AccountSteps($scenario);
        $I->loggedInAsActiveAccount();

        $this->route->changeUsername('password_0', 'bruce_wayne');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
