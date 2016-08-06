<?php
namespace codeception\api\functional;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\IdentityInfoRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class IdentityInfoCest {

    /**
     * @var IdentityInfoRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new IdentityInfoRoute($I);
    }

    public function testGetErrorIfNotEnoughPerms(OauthSteps $I) {
        $accessToken = $I->getAccessToken();
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'status' => 403,
        ]);
    }

    public function testGetInfo(OauthSteps $I) {
        $accessToken = $I->getAccessToken([S::ACCOUNT_INFO]);
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
        $accessToken = $I->getAccessToken([S::ACCOUNT_INFO, S::ACCOUNT_EMAIL]);
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
