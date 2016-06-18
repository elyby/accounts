<?php
namespace tests\codeception\api\functional\_steps;

use tests\codeception\api\_pages\OauthRoute;

class OauthSteps extends \tests\codeception\api\FunctionalTester {

    public function getAuthCode($online = true) {
        // TODO: по идее можно напрямую сделать зпись в базу, что ускорит процесс тестирования
        $this->loggedInAsActiveAccount();
        $route = new OauthRoute($this);
        $route->complete([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session' . ($online ? '' : ',offline_access'),
        ], ['accept' => true]);
        $this->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
        $response = json_decode($this->grabResponse(), true);
        preg_match('/code=([\w-]+)/', $response['redirectUri'], $matches);

        return $matches[1];
    }

    public function getRefreshToken() {
        // TODO: по идее можно напрямую сделать зпись в базу, что ускорит процесс тестирования
        $authCode = $this->getAuthCode(false);
        $route = new OauthRoute($this);
        $route->issueToken([
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
            'grant_type' => 'authorization_code',
        ]);

        $response = json_decode($this->grabResponse(), true);

        return $response['refresh_token'];
    }

}
