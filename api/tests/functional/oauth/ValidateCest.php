<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;
use Codeception\Attribute\Examples;
use Codeception\Example;

final class ValidateCest {

    public function successfullyValidateRequestForAuthFlow(FunctionalTester $I): void {
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

    public function successfullyValidateRequestForDeviceCode(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'user_code' => 'AAAABBBB',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
            'oAuth' => [
                'user_code' => 'AAAABBBB',
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
                ],
            ],
        ]);
    }

    public function unknownClientIdAuthFlow(FunctionalTester $I): void {
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

    /**
     * @param Example<array{string}> $case
     */
    #[Examples('http://ely.by.evil.com/callback')]
    #[Examples('http://ely.by@evil.com/callback')]
    #[Examples('http://ely.by:8080/callback')]
    #[Examples('https://ely.by/callback')]
    public function passRedirectUriThatOnlyStartsWithTheRegisteredOne(FunctionalTester $I, Example $case): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => $case[0],
            'response_type' => 'code',
            'scope' => 'account_info',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_client',
            'statusCode' => 401,
        ]);
    }

    public function invalidCodeForDeviceCode(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'user_code' => 'XXXXXXXX',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_user_code',
            'parameter' => 'user_code',
            'statusCode' => 401,
        ]);
    }

    public function expiredCodeForDeviceCode(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'user_code' => 'EXPIRED',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'expired_token',
            'parameter' => 'user_code',
            'statusCode' => 400,
        ]);
    }

    public function completedCodeForDeviceCode(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'user_code' => 'COMPLETED',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'used_user_code',
            'parameter' => 'user_code',
            'statusCode' => 400,
        ]);
    }

    public function invalidScopesAuthFlow(FunctionalTester $I): void {
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

    public function invalidScopesAuthFlowWithState(FunctionalTester $I): void {
        $I->wantToTest('that the state param is returned back along with the error params');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session some_wrong_scope',
            'state' => 'test-state',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_scope',
        ]);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertStringStartsWith('http://ely.by?state=test-state&error=invalid_scope&', $redirectUri);
    }

    public function requestInternalScopeAuthFlow(FunctionalTester $I): void {
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

    /**
     * @param Example<array{string, string, string}> $case
     */
    #[Examples('request', 'eyJhbGciOiJub25lIn0.eyJub25jZSI6ImFiYyJ9.', 'request_not_supported')]
    #[Examples('request', '', 'request_not_supported')]
    #[Examples('request_uri', 'https://client.example.org/request.jwt', 'request_uri_not_supported')]
    public function requestObjectIsNotSupported(FunctionalTester $I, Example $case): void {
        [$param, $value, $error] = $case;
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'test-state',
            $param => $value,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => $error,
            'statusCode' => 400,
        ]);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertStringStartsWith("http://ely.by?state=test-state&error={$error}&", $redirectUri);
    }

}
