<?php
namespace tests\codeception\api\functional\_steps;

use tests\codeception\api\_pages\LoginRoute;
use tests\codeception\api\FunctionalTester;

class AccountSteps extends FunctionalTester {

    public function loggedInAsActiveAccount() {
        $I = $this;
        $route = new LoginRoute($I);
        $route->login('Admin', 'password_0');
        $I->canSeeResponseIsJson();
    }

}
