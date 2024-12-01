<?php
namespace api\tests\functional\internal;

use api\tests\_pages\InternalRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class InfoCest {

    /**
     * @var InternalRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new InternalRoute($I);
    }

    public function testGetInfoById(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->info('id', '1');
        $this->expectSuccessResponse($I);
    }

    public function testGetInfoByUuid(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->info('uuid', 'df936908-b2e1-544d-96f8-2977ec213022');
        $this->expectSuccessResponse($I);
    }

    public function testGetInfoByUsername(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->info('username', 'admin');
        $this->expectSuccessResponse($I);
    }

    public function testInvalidParams(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->info('', '');
        $I->canSeeResponseCodeIs(400);
    }

    public function testAccountNotFound(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->info('username', 'this-user-not-exists');
        $I->canSeeResponseCodeIs(404);
    }

    /**
     * @param OauthSteps $I
     */
    private function expectSuccessResponse(OauthSteps $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'email' => 'admin@ely.by',
            'username' => 'Admin',
        ]);
    }

}
