<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\FunctionalTester;

final class CreateClientCest {

    public function testCreateWebApplication(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendPOST('/api/v1/oauth2/application', [
            'name' => 'My admin application',
            'description' => 'Application description.',
            'redirectUri' => 'http://some-site.com/oauth/ely',
            'websiteUrl' => 'http://some-site.com',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'my-admin-application',
                'name' => 'My admin application',
                'description' => 'Application description.',
                'websiteUrl' => 'http://some-site.com',
                'countUsers' => 0,
                'redirectUri' => 'http://some-site.com/oauth/ely',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.clientSecret');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.createdAt');
    }

    public function testCreateDesktopApplication(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendPOST('/api/v1/oauth2/desktop-application', [
            'name' => 'Mega Launcher',
            'description' => "Launcher's description.",
            'websiteUrl' => 'http://mega-launcher.com',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'mega-launcher',
                'type' => 'desktop-application',
                'name' => 'Mega Launcher',
                'description' => "Launcher's description.",
                'websiteUrl' => 'http://mega-launcher.com',
                'countUsers' => 0,
            ],
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.data.clientSecret');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.createdAt');
    }

    public function testCreateMinecraftServer(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendPOST('/api/v1/oauth2/minecraft-server', [
            'name' => 'My amazing server',
            'websiteUrl' => 'http://some-site.com',
            'minecraftServerIp' => 'hypixel.com:25565',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'my-amazing-server',
                'name' => 'My amazing server',
                'websiteUrl' => 'http://some-site.com',
                'minecraftServerIp' => 'hypixel.com:25565',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.clientSecret');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.createdAt');
    }

    public function testCreateApplicationWithTheSameNameAsDeletedApp(FunctionalTester $I): void {
        $I->wantTo('create application with the same name as the recently deleted application');
        $I->amAuthenticated('admin');
        $I->sendPOST('/api/v1/oauth2/application', [
            'name' => 'Deleted OAuth Client',
            'description' => '',
            'redirectUri' => 'http://some-site.com/oauth/ely',
            'websiteUrl' => 'http://some-site.com',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'deleted-oauth-client1',
            ],
        ]);
    }

    public function testCreateApplicationWithWrongParams(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendPOST('/api/v1/oauth2/application', []);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'name' => 'error.name_required',
                'redirectUri' => 'error.redirectUri_required',
            ],
        ]);
    }

}
