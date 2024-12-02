<?php
declare(strict_types=1);

namespace api\tests\functional\_steps;

use api\components\OAuth2\Repositories\PublicScopeRepository;
use api\tests\FunctionalTester;

class OauthSteps extends FunctionalTester {

    public function obtainAuthCode(array $permissions = []): string {
        $this->amAuthenticated();
        $this->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => implode(' ', $permissions),
        ]), ['accept' => true]);
        $this->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
        [$redirectUri] = $this->grabDataFromResponseByJsonPath('$.redirectUri');
        preg_match('/code=([^&$]+)/', (string)$redirectUri, $matches);

        return $matches[1];
    }

    public function getAccessToken(array $permissions = []): string {
        $authCode = $this->obtainAuthCode($permissions);
        $response = $this->issueToken($authCode);

        return $response['access_token'];
    }

    public function getRefreshToken(array $permissions = []): string {
        $authCode = $this->obtainAuthCode(array_merge([PublicScopeRepository::OFFLINE_ACCESS], $permissions));
        $response = $this->issueToken($authCode);

        return $response['refresh_token'];
    }

    public function issueToken(string $authCode): array {
        $this->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
        ]);

        return json_decode($this->grabResponse(), true);
    }

    public function getAccessTokenByClientCredentialsGrant(array $permissions = [], bool $useTrusted = true): string {
        $this->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $useTrusted ? 'trusted-client' : 'default-client',
            'client_secret' => $useTrusted ? 'tXBbyvMcyaOgHMOAXBpN2EC7uFoJAaL9' : 'AzWRy7ZjS1yRQUk2vRBDic8fprOKDB1W',
            'scope' => implode(' ', $permissions),
        ]);

        $response = json_decode($this->grabResponse(), true);

        return $response['access_token'];
    }

}
