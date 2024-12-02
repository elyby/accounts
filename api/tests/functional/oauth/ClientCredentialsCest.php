<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;

class ClientCredentialsCest {

    public function issueTokenWithPublicScopes(FunctionalTester $I): void {
        $I->wantTo('issue token as not trusted client and require only public scopes');
        // We don't have any public scopes yet for this grant, so the test runs with an empty set
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => '',
        ]);
        $this->assertSuccessResponse($I);
    }

    public function issueTokenWithInternalScopesAsNotTrustedClient(FunctionalTester $I): void {
        $I->wantTo('issue token as not trusted client and require some internal scope');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'block_account',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);
    }

    public function issueTokenWithInternalScopesAsTrustedClient(FunctionalTester $I): void {
        $I->wantTo('issue token as trusted client and require some internal scope');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'trusted-client',
            'client_secret' => 'tXBbyvMcyaOgHMOAXBpN2EC7uFoJAaL9',
            'scope' => 'block_account',
        ]);
        $this->assertSuccessResponse($I);
    }

    public function issueTokenByPassingInvalidClientId(FunctionalTester $I): void {
        $I->wantToTest('behavior on passing invalid client_id');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'invalid-client',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'scope' => 'block_account',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function issueTokenByPassingInvalidClientSecret(FunctionalTester $I): void {
        $I->wantTo('check behavior on passing invalid client_secret');
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'trusted-client',
            'client_secret' => 'invalid-secret',
            'scope' => 'block_account',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    private function assertSuccessResponse(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $I->cantSeeResponseJsonMatchesJsonPath('$.expires_in');
        $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
    }

}
