<?php
namespace api\tests\functional\oauth;

use api\components\OAuth2\Repositories\ScopeStorage as S;
use api\rbac\Permissions as P;
use api\tests\_pages\OauthRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class RefreshTokenCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testInvalidRefreshToken(OauthSteps $I) {
        $this->route->issueToken($this->buildParams(
            'some-invalid-refresh-token',
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM'
        ));
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'message' => 'The refresh token is invalid.',
        ]);
    }

    public function testRefreshToken(OauthSteps $I) {
        $refreshToken = $I->getRefreshToken();
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM'
        ));
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function testRefreshTokenWithSameScopes(OauthSteps $I) {
        $refreshToken = $I->getRefreshToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [P::MINECRAFT_SERVER_SESSION, S::OFFLINE_ACCESS]
        ));
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function testRefreshTokenTwice(OauthSteps $I) {
        $refreshToken = $I->getRefreshToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [P::MINECRAFT_SERVER_SESSION, S::OFFLINE_ACCESS]
        ));
        $this->canSeeRefreshTokenSuccess($I);

        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [P::MINECRAFT_SERVER_SESSION, S::OFFLINE_ACCESS]
        ));
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function testRefreshTokenWithNewScopes(OauthSteps $I) {
        $refreshToken = $I->getRefreshToken([P::MINECRAFT_SERVER_SESSION]);
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [P::MINECRAFT_SERVER_SESSION, S::OFFLINE_ACCESS, 'account_email']
        ));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);
    }

    private function buildParams($refreshToken = null, $clientId = null, $clientSecret = null, $scopes = []) {
        $params = ['grant_type' => 'refresh_token'];
        if ($refreshToken !== null) {
            $params['refresh_token'] = $refreshToken;
        }

        if ($clientId !== null) {
            $params['client_id'] = $clientId;
        }

        if ($clientSecret !== null) {
            $params['client_secret'] = $clientSecret;
        }

        if (!empty($scopes)) {
            if (is_array($scopes)) {
                $scopes = implode(',', $scopes);
            }

            $params['scope'] = $scopes;
        }

        return $params;
    }

    private function canSeeRefreshTokenSuccess(OauthSteps $I) {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

}
