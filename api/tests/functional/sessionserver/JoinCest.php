<?php
namespace api\tests\functional\sessionserver;

use api\rbac\Permissions as P;
use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\AuthserverSteps;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

class JoinCest {

    /**
     * @var SessionServerRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new SessionServerRoute($I);
    }

    public function joinByLegacyAuthserver(AuthserverSteps $I) {
        $I->wantTo('join to server, using legacy authserver access token');
        [$accessToken] = $I->amAuthenticated();
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByPassJsonInPost(AuthserverSteps $I) {
        $I->wantTo('join to server, passing data in body as encoded json');
        [$accessToken] = $I->amAuthenticated();
        $this->route->join(json_encode([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]));
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2Token(OauthSteps $I) {
        $I->wantTo('join to server, using modern oAuth2 generated token');
        $accessToken = $I->getAccessToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2TokenWithNotDashedUUID(OauthSteps $I) {
        $I->wantTo('join to server, using modern oAuth2 generated token and non dashed uuid');
        $accessToken = $I->getAccessToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908b2e1544d96f82977ec213022',
            'serverId' => uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByModernOauth2TokenWithoutPermission(OauthSteps $I) {
        $I->wantTo('join to server, using moder oAuth2 generated token, but without minecraft auth permission');
        $accessToken = $I->getAccessToken(['account_info', 'account_email']);
        $this->route->join([
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

    public function joinWithExpiredToken(OauthSteps $I) {
        $I->wantTo('join to some server with expired accessToken');
        $this->route->join([
            'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1MTgzMzQ3NDMsImV4cCI6MTUxODMzNDc5MCwiY2xpZW50X2lkIjoiZWx5Iiwic2NvcGUiOiJtaW5lY3JhZnRfc2VydmVyX3Nlc3Npb24iLCJzdWIiOiJlbHl8MSJ9.0mBXezB2p0eGuusZDklthR8xQLGo-v1qoP0GPdEPpYvckJMoHmlSqiW-2WwLlxGK0_J4KmYlp5vM4ynE14armw',
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
        $this->route->join([
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
        $this->route->join([
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
        $this->route->join([
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

    public function joinByAccountMarkedForDeletion(AuthserverSteps $I) {
        $this->route->join([
            'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1MTgzMzQ3NDMsImNsaWVudF9pZCI6ImVseSIsInNjb3BlIjoibWluZWNyYWZ0X3NlcnZlcl9zZXNzaW9uIiwic3ViIjoiZWx5fDE1In0.2qla7RzReBi2WtfgP3x8T6ZA0wn9HOrQo57xaZc2wMKPo1Zc49_o6w-5Ku1tbvzmESZfAxNQpfY4EwclEWjHYA',
            'selectedProfile' => '6383de63-8f85-4ed5-92b7-5401a1fa68cd',
            'serverId' => uuid(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid credentials',
        ]);
    }

    private function expectSuccessResponse(FunctionalTester $I): void {
        $I->seeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

}
