<?php
namespace tests\codeception\api\functional\sessionserver;

use common\models\OauthScope as S;
use Faker\Provider\Uuid;
use tests\codeception\api\_pages\SessionServerRoute;
use tests\codeception\api\functional\_steps\AuthserverSteps;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

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
        list($accessToken) = $I->amAuthenticated();
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByPassJsonInPost(AuthserverSteps $I) {
        $I->wantTo('join to server, passing data in body as encoded json');
        list($accessToken) = $I->amAuthenticated();
        $this->route->join(json_encode([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
        ]));
        $this->expectSuccessResponse($I);
    }

    public function joinByOauth2Token(OauthSteps $I) {
        $I->wantTo('join to server, using modern oAuth2 generated token');
        $accessToken = $I->getAccessToken([S::MINECRAFT_SERVER_SESSION]);
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
        ]);
        $this->expectSuccessResponse($I);
    }

    public function joinByModernOauth2TokenWithoutPermission(OauthSteps $I) {
        $I->wantTo('join to server, using moder oAuth2 generated token, but without minecraft auth permission');
        $accessToken = $I->getAccessToken([S::ACCOUNT_INFO, S::ACCOUNT_EMAIL]);
        $this->route->join([
            'accessToken' => $accessToken,
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
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
        $this->route->join([
            'accessToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
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
            'accessToken' => Uuid::uuid(),
            'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'serverId' => Uuid::uuid(),
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
            'serverId' => Uuid::uuid(),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
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
