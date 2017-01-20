<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\TwoFactorAuthRoute;
use tests\codeception\api\FunctionalTester;

class TwoFactorAuthCredentialsCest {

    /**
     * @var TwoFactorAuthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new TwoFactorAuthRoute($I);
    }

    public function testGetCredentials(FunctionalTester $I) {
        $I->loggedInAsActiveAccount();
        $this->route->credentials();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.secret');
        $I->canSeeResponseJsonMatchesJsonPath('$.uri');
        $I->canSeeResponseJsonMatchesJsonPath('$.qr');
    }

}
