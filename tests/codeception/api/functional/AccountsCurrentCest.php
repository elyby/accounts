<?php
namespace tests\codeception\api\functional;

use Codeception\Scenario;
use Codeception\Specify;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\functional\_steps\AccountSteps;
use tests\codeception\api\FunctionalTester;

class AccountsCurrentCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testCurrent(FunctionalTester $I, Scenario $scenario) {
        $I = new AccountSteps($scenario);
        $I->loggedInAsActiveAccount();

        $this->route->current();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'username' => 'Admin',
            'email' => 'admin@ely.by',
            'shouldChangePassword' => false,
        ]);
    }

}
