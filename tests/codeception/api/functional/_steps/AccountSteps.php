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
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');
        $jwt = $I->grabDataFromResponseByJsonPath('$.jwt')[0];
        $I->amBearerAuthenticated($jwt);
    }

    public function notLoggedIn() {
        $this->haveHttpHeader('Authorization', null);
    }

}
