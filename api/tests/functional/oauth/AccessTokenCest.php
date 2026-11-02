<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;

final class AccessTokenCest {

    public function successfullyIssueTokenWithUrlEncodedBody(OauthSteps $I): void {
        $I->wantTo('complete oauth flow and obtain access_token');
        $authCode = $I->obtainAuthCode();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->cantSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
        $I->cantSeeResponseJsonMatchesJsonPath('$.scope');
    }

    public function successfullyIssueTokenWithScopes(OauthSteps $I): void {
        $I->wantTo('see the granted scopes with their public names in the response');
        $authCode = $I->obtainAuthCode(['account_info', 'account_email', 'minecraft_server_session']);
        $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'scope' => 'account_info account_email minecraft_server_session',
        ]);

        [$accessToken] = $I->grabDataFromResponseByJsonPath('$.access_token');
        $payload = json_decode(base64_decode(strtr(explode('.', $accessToken)[1], '-_', '+/')), true);
        $I->assertSame('account_info account_email minecraft_server_session', $payload['scope']);
    }

    public function successfullyIssueOfflineTokenWithJsonEncodedBody(OauthSteps $I): void {
        $I->wantTo('complete oauth flow with offline_access scope and obtain access_token and refresh_token');
        $authCode = $I->obtainAuthCode(['offline_access']);
        $I->haveHttpHeader('Content-Type', 'application/json');
        // @phpstan-ignore argument.type (it does accept string an we need it to ensure, that JSON passes)
        $I->sendPOST('/api/oauth2/v1/token', json_encode([
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
        ]));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
        $I->canSeeResponseContainsJson([
            'scope' => 'offline_access',
        ]);
    }

    public function callEndpointWithByEmptyRequest(OauthSteps $I): void {
        $I->wantTo('check behavior on on request without any params');
        $I->sendPOST('/api/oauth2/v1/token');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'unsupported_grant_type',
            'error_description' => 'The authorization grant type is not supported by the authorization server.',
            'message' => 'The authorization grant type is not supported by the authorization server.',
        ]);
    }

    public function issueTokenByPassingInvalidAuthCode(OauthSteps $I): void {
        $I->wantTo('check behavior on passing invalid auth code');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => 'wrong-auth-code',
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
            'error_description' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token '
                . 'is invalid, expired, revoked, does not match the redirection URI used in the authorization request, '
                . 'or was issued to another client.',
            'message' => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token '
                . 'is invalid, expired, revoked, does not match the redirection URI used in the authorization request, '
                . 'or was issued to another client.',
        ]);
    }

    public function issueTokenByPassingInvalidRedirectUri(OauthSteps $I): void {
        $I->wantTo('check behavior on passing invalid redirect_uri');
        $authCode = $I->obtainAuthCode();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://some-other.domain',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
            'message' => 'Client authentication failed',
        ]);
    }

    public function issueTokenByPassingAnotherRegisteredRedirectUri(OauthSteps $I): void {
        $I->wantTo('check behavior on passing redirect_uri, which is allowed for the client, but differs from the authorization request');
        $authCode = $I->obtainAuthCode();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by/another/callback',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.error_description');
        $I->cantSeeResponseJsonMatchesJsonPath('$.access_token');
    }

    public function issueTokenWithoutClientSecret(OauthSteps $I): void {
        $authCode = $I->obtainAuthCode();
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            // no client_secret
            'redirect_uri' => 'http://ely.by',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.access_token');
    }

    public function issueTokenByPassingTheSameAuthCodeTwice(OauthSteps $I): void {
        $authCode = $I->obtainAuthCode();
        $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');

        $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.access_token');
    }

}
