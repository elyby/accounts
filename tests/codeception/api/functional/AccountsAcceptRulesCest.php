<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsAcceptRulesCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testCurrent(FunctionalTester $I) {
        $I->loggedInAsActiveAccount('Veleyaba', 'password_0');
        $this->route->acceptRules();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
