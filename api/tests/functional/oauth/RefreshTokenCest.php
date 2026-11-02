<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use common\models\OauthSession;

class RefreshTokenCest {

    public function refreshToken(OauthSteps $I): void {
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

    public function refreshTokenWithSameScopes(OauthSteps $I): void {
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

    public function refreshTokenTwice(OauthSteps $I): void {
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

    public function refreshTokenUsingLegacyToken(FunctionalTester $I): void {
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

    public function passInvalidRefreshToken(OauthSteps $I): void {
        $I->wantToTest('behaviour of the server when invalid refresh token passed');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'some-invalid-refresh-token',
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
            'message' => 'The refresh token is invalid.',
        ]);
    }

    public function requireNewScopes(OauthSteps $I): void {
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

    public function refreshTokenWithoutClientSecret(OauthSteps $I): void {
        $I->wantToTest('that the confidential client must be authenticated by its secret');
        $refreshToken = $I->getRefreshToken();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            // no client_secret
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function refreshTokenByAnotherClient(OauthSteps $I): void {
        $I->wantToTest('that the refresh token can be used only by the client it was issued to');
        $refreshToken = $I->getRefreshToken();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'tlauncher',
            'client_secret' => 'HsX-xXzdGiz3mcsqeEvrKHF47sqiaX94',
        ]);
        $this->canSeeInvalidRefreshToken($I);
    }

    public function refreshAccessTokenWithoutOfflineAccess(OauthSteps $I): void {
        $I->wantToTest('that the access token, issued without offline_access scope, can\'t be used as a refresh token');
        $accessToken = $I->getAccessToken(['account_info']);
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $accessToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $this->canSeeInvalidRefreshToken($I);
    }

    public function refreshTokenAfterSessionRevocation(OauthSteps $I): void {
        $I->wantToTest('that the refresh token stops working after the user revoked the access');
        $refreshToken = $I->getRefreshToken(['account_info']);
        /** @var OauthSession $session */
        $session = OauthSession::findOne(['account_id' => 1, 'client_id' => 'ely']);
        $session->revoked_at = time() + 1;
        $session->save();

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $this->canSeeInvalidRefreshToken($I);
    }

    public function refreshMinecraftAuthserverToken(FunctionalTester $I): void {
        $I->wantToTest('that the authserver token can\'t be exchanged for the OAuth2 token');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'admin',
            'password' => 'password_0',
            'clientToken' => 'ebc0a2a9-7f0b-4ff1-9b3b-7a0d1b6a9c0e',
        ]);
        $I->canSeeResponseCodeIs(200);
        [$accessToken] = $I->grabDataFromResponseByJsonPath('$.accessToken');

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $accessToken,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
        ]);
        $this->canSeeInvalidRefreshToken($I);
    }

    private function canSeeInvalidRefreshToken(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.access_token');
    }

    private function canSeeRefreshTokenSuccess(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

}
