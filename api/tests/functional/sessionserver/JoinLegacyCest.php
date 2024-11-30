<?php
namespace api\tests\functional\sessionserver;

use api\rbac\Permissions as P;
use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\AuthserverSteps;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

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
        [$accessToken] = $I->amAuthenticated();
        $this->route->joinLegacy([
            'sessionId' => $accessToken,
            'user' => 'Admin',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2TokenAndDifferentLetterCase(AuthserverSteps $I) {
        $I->wantTo('join to server by legacy protocol, using legacy authserver access token and different letter case');
        [$accessToken] = $I->amAuthenticated();
        $this->route->joinLegacy([
            'sessionId' => $accessToken,
            'user' => 'admin',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByNewSessionFormat(AuthserverSteps $I) {
        $I->wantTo('join to server by legacy protocol with new launcher session format, using legacy authserver');
        [$accessToken] = $I->amAuthenticated();
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2Token(OauthSteps $I) {
        $I->wantTo('join to server using modern oAuth2 generated token with new launcher session format');
        $accessToken = $I->getAccessToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => uuid(),
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
            'sessionId' => 'token:' . uuid() . ':' . uuid(),
            'user' => 'random-username',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->canSeeResponseContains('Ely.by authorization required');
    }

    public function joinWithAccessTokenWithoutMinecraftPermission(OauthSteps $I) {
        $I->wantTo('join to some server with wrong accessToken');
        $accessToken = $I->getAccessToken(['account_info']);
        $this->route->joinLegacy([
            'sessionId' => 'token:' . $accessToken . ':df936908-b2e1-544d-96f8-2977ec213022',
            'user' => 'Admin',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->canSeeResponseContains('Ely.by authorization required');
    }

    public function joinWithNilUuids(FunctionalTester $I) {
        $I->wantTo('join to some server by legacy protocol with nil accessToken and selectedProfile');
        $this->route->joinLegacy([
            'sessionId' => 'token:00000000-0000-0000-0000-000000000000:00000000-0000-0000-0000-000000000000',
            'user' => 'SomeUser',
            'serverId' => uuid(),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContains('credentials can not be null.');
    }

    public function joinByAccountMarkedForDeletion(FunctionalTester $I) {
        $I->wantTo('join to some server by legacy protocol with nil accessToken and selectedProfile');
        $this->route->joinLegacy([
            'sessionId' => 'token:239ba889-7020-4383-8d99-cd8c8aab4a2f:6383de63-8f85-4ed5-92b7-5401a1fa68cd',
            'user' => 'DeletedAccount',
            'serverId' => uuid(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContains('Ely.by authorization required');
    }

    private function expectSuccessResponse(FunctionalTester $I) {
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('OK');
    }

}
