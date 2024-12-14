<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;
use Codeception\Attribute\Before;

final class CompleteFlowCest {

    public function successfullyCompleteAuthCodeFlow(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function successfullyCompleteDeviceCodeFlow(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => 'AAAABBBB',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    #[Before('successfullyCompleteAuthCodeFlow')]
    public function successfullyCompleteAuthCodeFlowWithLessScopes(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get auth code with less scopes as passed in the previous request without accept param');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
        ]));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    #[Before('successfullyCompleteAuthCodeFlow')]
    public function successfullyCompleteAuthCodeFlowWithSameScopes(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get auth code with the same scopes as passed in the previous request without accept param');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function acceptRequiredOnFirstAuthRequest1(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get accept_required if I don\'t require any scope, but this is first time request');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => null,
            'statusCode' => 401,
        ]);
    }

    public function acceptRequiredOnFirstAuthRequest2(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get accept_required if I require some scopes on first time');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => null,
            'statusCode' => 401,
        ]);
    }

    public function acceptRequiredOnNewScope(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get accept_required if I have previous successful request, but now require some new scope');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]), ['accept' => true]);
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session account_info',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => null,
            'statusCode' => 401,
        ]);
    }

    public function completeAuthCodeFlowWithDecline(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]), ['accept' => false]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'access_denied',
            'parameter' => null,
            'statusCode' => 401,
            'redirectUri' => 'http://ely.by?error=access_denied&error_description=The+resource+owner+or+authorization+server+denied+the+request.&hint=The+user+denied+the+request',
        ]);
    }

    public function completeDeviceCodeFlowWithDecline(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => 'AAAABBBB',
        ]), ['accept' => false]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function tryToCompleteExpiredDeviceCodeFlow(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => 'EXPIRED',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'expired_token',
        ]);
    }

    public function tryToCompleteAlreadyCompletedDeviceCodeFlow(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => 'COMPLETED',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'used_user_code',
        ]);
    }

    public function invalidClientId(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('check behavior on invalid client id');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'non-exists-client',
            'redirect_uri' => 'http://some-resource.by',
            'response_type' => 'code',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_client',
            'statusCode' => 401,
        ]);
    }

    public function invalidScopes(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('check behavior on some invalid scopes');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session some_wrong_scope',
        ]));
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

    public function requestInternalScope(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('check behavior on request internal scope');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session block_account',
        ]), ['accept' => true]); // TODO: maybe remove?
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

    public function finalizeByAccountMarkedForDeletion(FunctionalTester $I): void {
        $I->amAuthenticated('DeletedAccount');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(403);
    }

}
