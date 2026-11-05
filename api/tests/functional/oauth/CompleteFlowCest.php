<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;
use Codeception\Attribute\Before;
use Codeception\Attribute\Examples;
use Codeception\Example;
use common\models\OauthSession;

final class CompleteFlowCest {

    public function successfullyCompleteAuthCodeFlow(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
            'state' => 'test-state',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertMatchesRegularExpression('#^http://ely\.by\?code=[^&]+&state=test-state&iss=http://localhost$#', $redirectUri);
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

    public function successfullyCompleteAuthCodeFlowWithLessScopes(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session account_info',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);

        $I->wantTo('get auth code with less scopes as passed in the previous request without accept param');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'account_info',
        ]));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    #[Before('successfullyCompleteAuthCodeFlow')]
    public function acceptRequiredWithoutScopesEvenWithExistingSession(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->wantTo('get accept_required if no scopes are requested, even when the client was approved previously');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'accept_required',
        ]);
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

    public function successfullyCompleteAuthCodeFlowWithSessionContainingPermissionsNames(FunctionalTester $I): void {
        $I->wantToTest('that previously stored sessions with the RBAC permissions names allow the auto approve');
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'test1',
            'redirect_uri' => 'http://test1.net',
            'response_type' => 'code',
            'scope' => 'account_info minecraft_server_session',
        ]));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');

        /** @var OauthSession $session */
        $session = OauthSession::findOne(['account_id' => 1, 'client_id' => 'test1']);
        $I->assertSame(['minecraft_server_session', 'account_info'], $session->getScopes());
    }

    /**
     * @param Example<array{string, string}> $case
     */
    #[Examples('profile', 'account_info')]
    #[Examples('account_info', 'profile')]
    #[Examples('email', 'account_email')]
    #[Examples('account_email', 'email')]
    public function successfullyCompleteAuthCodeFlowWithScopeAlias(FunctionalTester $I, Example $case): void {
        [$approvedScope, $requestedScope] = $case;
        $I->wantToTest('that the approved scope allows the auto approve of its OpenID Connect alias and vice versa');
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => $approvedScope,
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);

        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => $requestedScope,
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
            'error' => 'accept_required',
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
            'error' => 'accept_required',
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
            'error' => 'accept_required',
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
            'error' => 'access_denied',
            'redirectUri' => 'http://ely.by?error=access_denied&error_description=The%20user%20denied%20the%20request.&iss=http://localhost',
        ]);
    }

    public function completeAuthCodeFlowWithDeclineAndState(FunctionalTester $I): void {
        $I->wantToTest('that the state param is returned back along with the error params');
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
            'state' => 'test-state',
        ]), ['accept' => false]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'access_denied',
            'redirectUri' => 'http://ely.by?state=test-state&error=access_denied&error_description=The%20user%20denied%20the%20request.&iss=http://localhost',
        ]);
    }

    public function completeAuthCodeFlowWithDeclineAndQueryInRedirectUri(FunctionalTester $I): void {
        $I->wantToTest('that the error params are correctly appended to the redirect uri with its own query');
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by/callback?param=value',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session',
            'state' => 'test-state',
        ]), ['accept' => false]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'access_denied',
            'redirectUri' => 'http://ely.by/callback?param=value&state=test-state&error=access_denied&error_description=The%20user%20denied%20the%20request.&iss=http://localhost',
        ]);
    }

    #[Before('successfullyCompleteAuthCodeFlowWithLessScopes')]
    public function acceptRequiredForDeviceCodeFlowEvenWithExistingSession(FunctionalTester $I): void {
        $I->wantTo('get accept_required for the device code flow, even when the client was approved with the same scopes');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => 'AAAABBBB',
        ]));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'accept_required',
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
            'error' => 'invalid_client',
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
            'error' => 'invalid_scope',
            'parameter' => 'scope',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function requestThatCanNotBeHandledByAnyGrant(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'token',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'unsupported_grant_type',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function requestObjectIsNotSupported(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'openid',
            'request_uri' => 'https://client.example.org/request.jwt',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'request_uri_not_supported',
        ]);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertStringStartsWith('http://ely.by?error=request_uri_not_supported&', $redirectUri);
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
            'error' => 'invalid_scope',
            'parameter' => 'scope',
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
