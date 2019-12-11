<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;

class AccessTokenCest {

    public function successfullyIssueToken(OauthSteps $I) {
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
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

    public function successfullyIssueOfflineToken(OauthSteps $I) {
        $I->wantTo('complete oauth flow with offline_access scope and obtain access_token and refresh_token');
        $authCode = $I->obtainAuthCode(['offline_access']);
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
        $I->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

    public function callEndpointWithByEmptyRequest(OauthSteps $I) {
        $I->wantTo('check behavior on on request without any params');
        $I->sendPOST('/api/oauth2/v1/token');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'unsupported_grant_type',
            'message' => 'The authorization grant type is not supported by the authorization server.',
        ]);
    }

    public function issueTokenByPassingInvalidAuthCode(OauthSteps $I) {
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
            'error' => 'invalid_request',
            'message' => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed. Check the "code" parameter.',
        ]);
    }

    public function issueTokenByPassingInvalidRedirectUri(OauthSteps $I) {
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
            'message' => 'Client authentication failed',
        ]);
    }

}
