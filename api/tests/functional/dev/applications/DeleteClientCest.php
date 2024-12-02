<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\_pages\OauthRoute;
use api\tests\FunctionalTester;

class DeleteClientCest {

    private OauthRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new OauthRoute($I);
    }

    public function testDelete(FunctionalTester $I): void {
        $I->amAuthenticated('TwoOauthClients');
        $this->route->deleteClient('first-test-oauth-client');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
