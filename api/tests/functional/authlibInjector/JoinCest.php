<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\rbac\Permissions as P;
use api\tests\functional\_steps\AuthserverSteps;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use Codeception\Example;
use function Ramsey\Uuid\v4 as uuid;

class JoinCest {

    public function joinByLegacyAuthserver(AuthserverSteps $I) {
        $I->wantTo('join to server, using legacy authserver access token');
        [$accessToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByPassJsonInPost(AuthserverSteps $I) {
        $I->wantTo('join to server, passing data in body as encoded json');
        [$accessToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    /**
     * @example ["df936908-b2e1-544d-96f8-2977ec213022"]
     * @example ["df936908b2e1544d96f82977ec213022"]
     */
    public function joinByOauth2Token(OauthSteps $I, Example $case) {
        $I->wantTo('join to server, using modern oAuth2 generated token');
        $accessToken = $I->getAccessToken([P::MINECRAFT_SERVER_SESSION]);
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => $accessToken,
            'selectedProfile' => $case[0],
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByModernOauth2TokenWithoutPermission(OauthSteps $I) {
        $I->wantTo('join to server, using moder oAuth2 generated token, but without minecraft auth permission');
        $accessToken = $I->getAccessToken(['account_info', 'account_email']);
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'The token does not have required scope.',
        ]);
    }

    public function joinWithExpiredToken(FunctionalTester $I) {
        $I->wantTo('join to some server with expired accessToken');
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Expired access_token.',
        ]);
    }

    public function wrongArguments(FunctionalTester $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'wrong' => 'argument',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function joinWithWrongAccessToken(FunctionalTester $I) {
        $I->wantTo('join to some server with wrong accessToken');
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => uuid(),
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid access_token.',
        ]);
    }

    public function joinWithNilUuids(FunctionalTester $I) {
        $I->wantTo('join to some server with nil accessToken and selectedProfile');
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => '00000000-0000-0000-0000-000000000000',
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function joinByAccountMarkedForDeletion(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/sessionserver/session/minecraft/join', [
            'accessToken' => '239ba889-7020-4383-8d99-cd8c8aab4a2f',
            'selectedProfile' => '6383de63-8f85-4ed5-92b7-5401a1fa68cd',
            'serverId' => uuid(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid credentials',
        ]);
    }

    private function expectSuccessResponse(FunctionalTester $I) {
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'OK',
        ]);
    }

}
