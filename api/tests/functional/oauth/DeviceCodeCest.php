<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;
use Codeception\Attribute\Examples;
use Codeception\Example;

final class DeviceCodeCest {

    public function initiateFlow(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/devicecode', [
            'client_id' => 'ely',
            'scope' => 'account_info minecraft_server_session',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'verification_uri' => 'http://localhost/code',
            'interval' => 5,
            'expires_in' => 600,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.device_code');
        $I->canSeeResponseJsonMatchesJsonPath('$.user_code');
    }

    public function pollPendingDeviceCode(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => 'ely',
            'device_code' => 'nKuYFfwckZywqU8iUKv3ek4VtiMiMCkiC0YTZFPbWycSxdRpHiYP2wnv3S0KHBgYky8fRDqfhhCqzke7',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'authorization_pending',
        ]);
    }

    public function pollExpiredDeviceCode(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => 'ely',
            'device_code' => 'ZFPbWycSxdRpHiYP2wnv3S0KHBgYky8fRDqfhhCqzke7nKuYFfwckZywqU8iUKv3ek4VtiMiMCkiC0YT',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'expired_token',
            'message' => 'The `device_code` has expired and the device authorization session has concluded.',
        ]);
    }

    /**
     * @param Example<array{boolean}> $case
     */
    #[Examples(true)]
    #[Examples(false)]
    public function finishFlowWithApprovedCode(FunctionalTester $I, Example $case): void {
        // Initialize flow
        $I->sendPOST('/api/oauth2/v1/devicecode', [
            'client_id' => 'ely',
            'scope' => 'account_info minecraft_server_session',
        ]);
        $I->canSeeResponseCodeIs(200);

        ['user_code' => $userCode, 'device_code' => $deviceCode] = json_decode($I->grabResponse(), true);

        // Approve device code by the user
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'user_code' => $userCode,
        ]), ['accept' => $case[0]]);
        $I->canSeeResponseCodeIs(200);

        // Finish flow by obtaining the access token
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => 'ely',
            'device_code' => $deviceCode,
        ]);
        if ($case[0]) {
            $I->canSeeResponseCodeIs(200);
            $I->canSeeResponseContainsJson([
                'token_type' => 'Bearer',
            ]);
            $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
            $I->cantSeeResponseJsonMatchesJsonPath('$.expires_in');
            $I->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
        } else {
            $I->canSeeResponseCodeIs(401);
            $I->canSeeResponseContainsJson([
                'error' => 'access_denied',
                'message' => 'The resource owner or authorization server denied the request.',
            ]);
        }
    }

    public function getAnErrorForUnknownClient(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/devicecode', [
            'client_id' => 'invalid-client',
            'scope' => 'account_info minecraft_server_session',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function getAnErrorForInvalidScopes(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/devicecode', [
            'client_id' => 'ely',
            'scope' => 'unknown-scope',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);
    }

}
