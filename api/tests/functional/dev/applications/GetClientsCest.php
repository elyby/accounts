<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\FunctionalTester;

final class GetClientsCest {

    public function testGet(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendGET('/api/v1/oauth2/admin-oauth-client');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'clientId' => 'admin-oauth-client',
            'clientSecret' => 'FKyO71iCIlv4YM2IHlLbhsvYoIJScUzTZt1kEK7DQLXXYISLDvURVXK32Q58sHWS',
            'type' => 'application',
            'name' => 'Admin\'s oauth client',
            'description' => 'Personal oauth client',
            'redirectUri' => 'http://some-site.com/oauth/ely',
            'websiteUrl' => '',
            'createdAt' => 1519254133,
        ]);
    }

    public function testGetNotOwn(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendGET('/api/v1/oauth2/another-test-oauth-client');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'status' => 403,
            'message' => 'You are not allowed to perform this action.',
        ]);
    }

    public function testGetAllPerAccountList(FunctionalTester $I): void {
        $accountId = $I->amAuthenticated('TwoOauthClients');
        $I->sendGET("/api/v1/accounts/{$accountId}/oauth2/clients");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'clientId' => 'first-test-oauth-client',
                'clientSecret' => 'Zt1kEK7DQLXXYISLDvURVXK32Q58sHWSFKyO71iCIlv4YM2IHlLbhsvYoIJScUzT',
                'type' => 'application',
                'name' => 'First test oauth client',
                'description' => 'Some description to the first oauth client',
                'redirectUri' => 'http://some-site-1.com/oauth/ely',
                'websiteUrl' => '',
                'countUsers' => 0,
                'createdAt' => 1519487434,
            ],
            [
                'clientId' => 'another-test-oauth-client',
                'clientSecret' => 'URVXK32Q58sHWSFKyO71iCIlv4YM2Zt1kEK7DQLXXYISLDvIHlLbhsvYoIJScUzT',
                'type' => 'minecraft-server',
                'name' => 'Another test oauth client',
                'websiteUrl' => '',
                'minecraftServerIp' => '136.243.88.97:25565',
                'createdAt' => 1519487472,
            ],
        ]);
    }

    public function testGetAllPerNotOwnAccount(FunctionalTester $I): void {
        $I->amAuthenticated('TwoOauthClients');
        $I->sendGET('/api/v1/accounts/1/oauth2/clients');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'status' => 403,
            'message' => 'You are not allowed to perform this action.',
        ]);
    }

}
