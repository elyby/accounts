<?php
namespace tests\codeception\api\oauth;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class AccessTokenCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testIssueTokenWithWrongArgs(OauthSteps $I) {
        $I->wantTo('check behavior on on request without any credentials');
        $this->route->issueToken();
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'message' => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed. Check the "grant_type" parameter.',
        ]);

        $I->wantTo('check behavior on passing invalid auth code');
        $this->route->issueToken($this->buildParams(
            'wrong-auth-code',
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'http://ely.by'
        ));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'message' => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed. Check the "code" parameter.',
        ]);

        $authCode = $I->getAuthCode();
        $I->wantTo('check behavior on passing invalid redirect_uri');
        $this->route->issueToken($this->buildParams(
            $authCode,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'http://some-other.domain'
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
            'message' => 'Client authentication failed.',
        ]);
    }

    public function testIssueToken(OauthSteps $I) {
        $authCode = $I->getAuthCode();
        $this->route->issueToken($this->buildParams(
            $authCode,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'http://ely.by'
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
    }

    public function testIssueTokenWithRefreshToken(OauthSteps $I) {
        $authCode = $I->getAuthCode([S::OFFLINE_ACCESS]);
        $this->route->issueToken($this->buildParams(
            $authCode,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'http://ely.by'
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
    }

    private function buildParams($code = null, $clientId = null, $clientSecret = null, $redirectUri = null) {
        $params = ['grant_type' => 'authorization_code'];
        if ($code !== null) {
            $params['code'] = $code;
        }

        if ($clientId !== null) {
            $params['client_id'] = $clientId;
        }

        if ($clientSecret !== null) {
            $params['client_secret'] = $clientSecret;
        }

        if ($redirectUri !== null) {
            $params['redirect_uri'] = $redirectUri;
        }

        return $params;
    }

}
