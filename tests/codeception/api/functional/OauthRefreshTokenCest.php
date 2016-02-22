<?php
namespace tests\codeception\api;

use Codeception\Scenario;
use common\models\OauthScope;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use Yii;

class OauthRefreshTokenCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testRefreshToken(FunctionalTester $I, Scenario $scenario) {
        $I = new OauthSteps($scenario);
        $refreshToken = $I->getRefreshToken();
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM'
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

    public function testRefreshTokenWithSameScopes(FunctionalTester $I, Scenario $scenario) {
        $I = new OauthSteps($scenario);
        $refreshToken = $I->getRefreshToken();
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [OauthScope::MINECRAFT_SERVER_SESSION, OauthScope::OFFLINE_ACCESS]
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

    public function testRefreshTokenWithNewScopes(FunctionalTester $I, Scenario $scenario) {
        $I = new OauthSteps($scenario);
        $refreshToken = $I->getRefreshToken();
        $this->route->issueToken($this->buildParams(
            $refreshToken,
            'ely',
            'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            [OauthScope::MINECRAFT_SERVER_SESSION, OauthScope::OFFLINE_ACCESS, 'change_skin']
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

}
