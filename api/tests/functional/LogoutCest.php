<?php
namespace api\tests\functional;

use api\tests\_pages\AuthenticationRoute;
use api\tests\FunctionalTester;

class LogoutCest {

    public function testLoginEmailOrUsername(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->amAuthenticated();
        $route->logout();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
