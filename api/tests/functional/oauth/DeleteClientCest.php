<?php
namespace api\tests\functional\oauth;

use api\tests\_pages\OauthRoute;
use api\tests\FunctionalTester;

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
