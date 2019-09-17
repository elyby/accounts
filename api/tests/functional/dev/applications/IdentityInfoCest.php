<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\_pages\IdentityInfoRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class IdentityInfoCest {

    /**
     * @var IdentityInfoRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new IdentityInfoRoute($I);
    }

    public function testGetErrorIfNoAccessToken(OauthSteps $I) {
        $I->wantToTest('behavior when this endpoint called without Authorization header');
        $this->route->info();
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'status' => 401,
            'message' => 'Your request was made with invalid credentials.',
        ]);
    }

    public function testGetErrorIfNotEnoughPerms(OauthSteps $I) {
        $I->wantToTest('behavior when this endpoint called with token, that have not enough scopes');
        $accessToken = $I->getAccessToken();
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'status' => 403,
            'message' => 'You are not allowed to perform this action.',
        ]);
    }

    public function testGetInfo(OauthSteps $I) {
        $accessToken = $I->getAccessToken(['account_info']);
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'registeredAt' => 1451775316,
            'profileLink' => 'http://ely.by/u1',
            'preferredLanguage' => 'en',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.email');
    }

    public function testGetInfoWithEmail(OauthSteps $I) {
        $accessToken = $I->getAccessToken(['account_info', 'account_email']);
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'registeredAt' => 1451775316,
            'profileLink' => 'http://ely.by/u1',
            'preferredLanguage' => 'en',
            'email' => 'admin@ely.by',
        ]);
    }

}
