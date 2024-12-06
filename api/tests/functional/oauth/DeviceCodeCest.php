<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;

final class DeviceCodeCest {

    public function initiateFlow(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/device', [
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
        $I->sendPOST('/api/oauth2/v1/device', [
            'client_id' => 'ely',
            'scope' => 'account_info minecraft_server_session',
        ]);
        $I->canSeeResponseCodeIs(200);

        ['user_code' => $userCode, 'device_code' => $deviceCode] = json_decode($I->grabResponse(), true);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => 'ely',
            'device_code' => $deviceCode,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'authorization_pending',
        ]);
    }

    public function getAnErrorForUnknownClient(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/device', [
            'client_id' => 'invalid-client',
            'scope' => 'account_info minecraft_server_session',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function getAnErrorForInvalidScopes(FunctionalTester $I): void {
        $I->sendPOST('/api/oauth2/v1/device', [
            'client_id' => 'ely',
            'scope' => 'unknown-scope',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_scope',
        ]);
    }

}
