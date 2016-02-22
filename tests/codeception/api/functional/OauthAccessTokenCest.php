<?php
namespace tests\codeception\api;

use Codeception\Scenario;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use Yii;

class OauthAccessTokenCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testIssueTokenWithWrongArgs(FunctionalTester $I) {
        $I->wantTo('check behavior on on request without any credentials');
        $this->route->issueToken();
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
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
        ]);
    }

    public function testIssueToken(FunctionalTester $I, Scenario $scenario) {
        $I = new OauthSteps($scenario);
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

    public function testIssueTokenWithRefreshToken(FunctionalTester $I, Scenario $scenario) {
        $I = new OauthSteps($scenario);
        $authCode = $I->getAuthCode(false);
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
