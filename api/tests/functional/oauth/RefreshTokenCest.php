<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class RefreshTokenCest {

    public function refreshToken(OauthSteps $I) {
        $I->wantTo('refresh token without passing the desired scopes');
        $refreshToken = $I->getRefreshToken();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function refreshTokenWithSameScopes(OauthSteps $I) {
        $refreshToken = $I->getRefreshToken(['minecraft_server_session']);
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'minecraft_server_session',
        ]);
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function refreshTokenTwice(OauthSteps $I) {
        $I->wantTo('refresh token two times in a row and ensure, that token isn\'t rotating');
        $refreshToken = $I->getRefreshToken(['minecraft_server_session']);
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'minecraft_server_session',
        ]);
        $this->canSeeRefreshTokenSuccess($I);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'minecraft_server_session',
        ]);
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function refreshTokenUsingLegacyToken(FunctionalTester $I) {
        $I->wantTo('refresh token using the legacy token');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'op7kPGAgHlsXRBJtkFg7wKOTpodvtHVW5NxR7Tjr',
            'client_id' => 'test1',
            'client_secret' => 'eEvrKHF47sqiaX94HsX-xXzdGiz3mcsq',
            'scope' => 'minecraft_server_session account_info',
        ]);
        $this->canSeeRefreshTokenSuccess($I);
    }

    public function passInvalidRefreshToken(OauthSteps $I) {
        $I->wantToTest('behaviour of the server when invalid refresh token passed');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'some-invalid-refresh-token',
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'message' => 'The refresh token is invalid.',
        ]);
    }

    public function requireNewScopes(OauthSteps $I) {
        $I->wantToTest('behavior when required the new scope that was not issued with original token');
        $refreshToken = $I->getRefreshToken(['minecraft_server_session']);
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'minecraft_server_session account_email',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);
    }

    private function canSeeRefreshTokenSuccess(FunctionalTester $I) {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

}
