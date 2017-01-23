<?php
namespace tests\codeception\api\functional\_steps;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\FunctionalTester;

class OauthSteps extends FunctionalTester {

    public function getAuthCode(array $permissions = []) {
        // TODO: по идее можно напрямую сделать запись в базу, что ускорит процесс тестирования
        $this->amAuthenticated();
        $route = new OauthRoute($this);
        $route->complete([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => implode(',', $permissions),
        ], ['accept' => true]);
        $this->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
        $response = json_decode($this->grabResponse(), true);
        preg_match('/code=([\w-]+)/', $response['redirectUri'], $matches);

        return $matches[1];
    }

    public function getAccessToken(array $permissions = []) {
        $authCode = $this->getAuthCode($permissions);
        $response = $this->issueToken($authCode);

        return $response['access_token'];
    }

    public function getRefreshToken(array $permissions = []) {
        // TODO: по идее можно напрямую сделать запись в базу, что ускорит процесс тестирования
        $authCode = $this->getAuthCode(array_merge([S::OFFLINE_ACCESS], $permissions));
        $response = $this->issueToken($authCode);

        return $response['refresh_token'];
    }

    public function issueToken($authCode) {
        $route = new OauthRoute($this);
        $route->issueToken([
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
            'grant_type' => 'authorization_code',
        ]);

        return json_decode($this->grabResponse(), true);
    }

    public function getAccessTokenByClientCredentialsGrant(array $permissions = [], $useTrusted = true) {
        $route = new OauthRoute($this);
        $route->issueToken([
            'client_id' => $useTrusted ? 'trusted-client' : 'default-client',
            'client_secret' => $useTrusted ? 'tXBbyvMcyaOgHMOAXBpN2EC7uFoJAaL9' : 'AzWRy7ZjS1yRQUk2vRBDic8fprOKDB1W',
            'grant_type' => 'client_credentials',
            'scope' => implode(',', $permissions),
        ]);

        $response = json_decode($this->grabResponse(), true);

        return $response['access_token'];
    }

}
