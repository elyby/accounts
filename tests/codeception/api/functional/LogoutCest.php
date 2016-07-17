<?php
namespace tests\codeception\api;

use tests\codeception\api\_pages\AuthenticationRoute;

class LogoutCest {

    public function testLoginEmailOrUsername(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->loggedInAsActiveAccount();
        $route->logout();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
