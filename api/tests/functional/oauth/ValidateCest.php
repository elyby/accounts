<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;

class ValidateCest {

    public function completelyValidateValidRequest(FunctionalTester $I) {
        $I->wantTo('validate and obtain information about new oauth request');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session account_info account_email',
            'state' => 'test-state',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
            'oAuth' => [
                'client_id' => 'ely',
                'redirect_uri' => 'http://ely.by',
                'response_type' => 'code',
                'scope' => 'minecraft_server_session account_info account_email',
                'state' => 'test-state',
            ],
            'client' => [
                'id' => 'ely',
                'name' => 'Ely.by',
                'description' => 'Всем знакомое елуби',
            ],
            'session' => [
                'scopes' => [
                    'minecraft_server_session',
                    'account_info',
                    'account_email',
                ],
            ],
        ]);
    }

    public function completelyValidateValidRequestWithOverriddenDescription(FunctionalTester $I) {
        $I->wantTo('validate and get information with description replacement');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'description' => 'all familiar eliby',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'client' => [
                'description' => 'all familiar eliby',
            ],
        ]);
    }

    public function unknownClientId(FunctionalTester $I) {
        $I->wantTo('check behavior on invalid client id');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'non-exists-client',
            'redirect_uri' => 'http://some-resource.by',
            'response_type' => 'code',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_client',
            'statusCode' => 401,
        ]);
    }

    public function invalidScopes(FunctionalTester $I) {
        $I->wantTo('check behavior on some invalid scopes');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session some_wrong_scope',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_scope',
            'parameter' => 'some_wrong_scope',
            'statusCode' => 400,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function requestInternalScope(FunctionalTester $I) {
        $I->wantTo('check behavior on request internal scope');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session block_account',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_scope',
            'parameter' => 'block_account',
            'statusCode' => 400,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

}
