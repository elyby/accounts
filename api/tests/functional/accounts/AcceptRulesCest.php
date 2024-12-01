<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;

class AcceptRulesCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testCurrent(FunctionalTester $I): void {
        $I->amAuthenticated('Veleyaba');
        $this->route->acceptRules(9);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
