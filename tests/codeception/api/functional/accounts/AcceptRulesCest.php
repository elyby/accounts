<?php
namespace tests\codeception\api\functional\accounts;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AcceptRulesCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testCurrent(FunctionalTester $I) {
        $I->amAuthenticated('Veleyaba');
        $this->route->acceptRules(9);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
