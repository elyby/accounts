<?php
namespace tests\codeception\api;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\functional\_steps\OauthSteps;

class OauthClientCredentialsGrantCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testIssueTokenWithWrongArgs(FunctionalTester $I) {
        $I->wantTo('check behavior on on request without any credentials');
        $this->route->issueToken($this->buildParams());
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);

        $I->wantTo('check behavior on passing invalid client_id');
        $this->route->issueToken($this->buildParams(
            'invalid-client',
            'invalid-secret',
            ['invalid-scope']
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);

        $I->wantTo('check behavior on passing invalid client_secret');
        $this->route->issueToken($this->buildParams(
            'ely',
            'invalid-secret',
            ['invalid-scope']
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);

        $I->wantTo('check behavior on passing invalid client_secret');
        $this->route->issueToken($this->buildParams(
            'ely',
            'invalid-secret',
            ['invalid-scope']
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function testIssueTokenWithPublicScopes(OauthSteps $I) {
        // TODO: у нас пока нет публичных скоупов, поэтому тест прогоняется с пустым набором
        $this->route->issueToken($this->buildParams(
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            []
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
    }

    public function testIssueTokenWithInternalScopes(OauthSteps $I) {
        $this->route->issueToken($this->buildParams(
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [S::ACCOUNT_BLOCK]
        ));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);

        $this->route->issueToken($this->buildParams(
            'trusted-client',
            'tXBbyvMcyaOgHMOAXBpN2EC7uFoJAaL9',
            [S::ACCOUNT_BLOCK]
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
    }

    private function buildParams($clientId = null, $clientSecret = null, array $scopes = null) {
        $params = ['grant_type' => 'client_credentials'];
        if ($clientId !== null) {
            $params['client_id'] = $clientId;
        }

        if ($clientSecret !== null) {
            $params['client_secret'] = $clientSecret;
        }

        if ($scopes !== null) {
            $params['scope'] = implode(',', $scopes);
        }

        return $params;
    }

}
