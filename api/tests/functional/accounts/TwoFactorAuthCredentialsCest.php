<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;

class TwoFactorAuthCredentialsCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testGetCredentials(FunctionalTester $I) {
        $accountId = $I->amAuthenticated();
        $this->route->getTwoFactorAuthCredentials($accountId);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.secret');
        $I->canSeeResponseJsonMatchesJsonPath('$.uri');
        $I->canSeeResponseJsonMatchesJsonPath('$.qr');
    }

}
