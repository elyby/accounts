<?php
namespace tests\codeception\api\oauth;

use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\FunctionalTester;

class DeleteClientCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testDelete(FunctionalTester $I) {
        $I->amAuthenticated('TwoOauthClients');
        $this->route->deleteClient('first-test-oauth-client');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
