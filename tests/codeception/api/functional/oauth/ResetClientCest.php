<?php
namespace tests\codeception\api\oauth;

use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\FunctionalTester;

class ResetClientCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testReset(FunctionalTester $I) {
        $I->amAuthenticated('TwoOauthClients');
        $this->route->resetClient('first-test-oauth-client');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'first-test-oauth-client',
                'clientSecret' => 'Zt1kEK7DQLXXYISLDvURVXK32Q58sHWSFKyO71iCIlv4YM2IHlLbhsvYoIJScUzT',
                'name' => 'First test oauth client',
                'description' => 'Some description to the first oauth client',
                'redirectUri' => 'http://some-site-1.com/oauth/ely',
                'websiteUrl' => '',
                'countUsers' => 0,
                'createdAt' => 1519487434,
            ],
        ]);
    }

    public function testResetWithSecretChanging(FunctionalTester $I) {
        $I->amAuthenticated('TwoOauthClients');
        $this->route->resetClient('first-test-oauth-client', true);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'clientId' => 'first-test-oauth-client',
                'name' => 'First test oauth client',
                'description' => 'Some description to the first oauth client',
                'redirectUri' => 'http://some-site-1.com/oauth/ely',
                'websiteUrl' => '',
                'countUsers' => 0,
                'createdAt' => 1519487434,
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.clientSecret');
        $secret = $I->grabDataFromResponseByJsonPath('$.data.clientSecret')[0];
        $I->assertNotEquals('Zt1kEK7DQLXXYISLDvURVXK32Q58sHWSFKyO71iCIlv4YM2IHlLbhsvYoIJScUzT', $secret);
    }

}
