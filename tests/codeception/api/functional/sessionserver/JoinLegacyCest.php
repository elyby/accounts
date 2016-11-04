<?php
namespace tests\codeception\api\functional\sessionserver;

use common\models\OauthScope as S;
use Faker\Provider\Uuid;
use tests\codeception\api\_pages\SessionServerRoute;
use tests\codeception\api\functional\_steps\AuthserverSteps;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class JoinLegacyCest {

    /**
     * @var SessionServerRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new SessionServerRoute($I);
    }

    public function joinByLegacyAuthserver(AuthserverSteps $I) {
        $I->wantTo('join to server by legacy protocol, using legacy authserver access token');
        list($accessToken) = $I->amAuthenticated();
        $this->route->joinLegacy([
            'sessionId' => $accessToken,
            'user' => 'Admin',
            'serverId' => Uuid::uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByNewSessionFormat(AuthserverSteps $I) {
        $I->wantTo('join to server by legacy protocol with new launcher session format, using legacy authserver');
        list($accessToken) = $I->amAuthenticated();
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':' . 'df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => Uuid::uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2Token(OauthSteps $I) {
        $I->wantTo('join to server using modern oAuth2 generated token with new launcher session format');
        $accessToken = $I->getAccessToken([S::MINECRAFT_SERVER_SESSION]);
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':' . 'df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => Uuid::uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function wrongArguments(FunctionalTester $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->joinLegacy([
            'wrong' => 'argument',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContains('credentials can not be null.');
    }

    public function joinWithWrongAccessToken(FunctionalTester $I) {
        $I->wantTo('join to some server with wrong accessToken');
        $this->route->joinLegacy([
            'sessionId' => 'token:' . Uuid::uuid() . ':' . Uuid::uuid(),
            'user' => 'random-username',
            'serverId' => Uuid::uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->canSeeResponseContains('Ely.by authorization required');
    }

    public function joinWithAccessTokenWithoutMinecraftPermission(OauthSteps $I) {
        $I->wantTo('join to some server with wrong accessToken');
        $accessToken = $I->getAccessToken([S::ACCOUNT_INFO]);
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':' . 'df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => Uuid::uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->canSeeResponseContains('Ely.by authorization required');
    }

    public function joinWithNilUuids(FunctionalTester $I) {
        $I->wantTo('join to some server by legacy protocol with nil accessToken and selectedProfile');
        $this->route->joinLegacy([
            'sessionId' => 'token:00000000-0000-0000-0000-000000000000:00000000-0000-0000-0000-000000000000',
            'user' => 'SomeUser',
            'serverId' => Uuid::uuid(),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContains('credentials can not be null.');
    }

    private function expectSuccessResponse(FunctionalTester $I) {
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('OK');
    }

}
