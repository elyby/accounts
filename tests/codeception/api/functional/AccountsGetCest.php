<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsGetCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testGetInfo(FunctionalTester $I) {
        $accountId = $I->amAuthenticated();

        $this->route->get($accountId);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'isOtpEnabled' => false,
            'email' => 'admin@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => false,
            'elyProfileLink' => 'http://ely.by/u1',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testGetWithNotAcceptedLatestRules(FunctionalTester $I) {
        $accountId = $I->amAuthenticated('Veleyaba');

        $this->route->get($accountId);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 9,
            'uuid' => '410462d3-8e71-47cc-bac6-64f77f88cf80',
            'username' => 'Veleyaba',
            'email' => 'veleyaba@gmail.com',
            'isOtpEnabled' => false,
            'lang' => 'en',
            'isActive' => true,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => true,
            'elyProfileLink' => 'http://ely.by/u9',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testGetInfoWithExpiredToken(FunctionalTester $I) {
        // Устанавливаем заведомо истёкший токен
        $I->amBearerAuthenticated(
            // TODO: обновить токен
            'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpYXQiO' .
            'jE0NjQ2Mjc1NDUsImV4cCI6MTQ2NDYzMTE0NSwianRpIjoxfQ.9c1mm0BK-cuW1qh15F12s2Fh37IN43YeeZeU4DFtlrE'
        );

        $this->route->get(1);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Token expired',
            'code' => 0,
            'status' => 401,
        ]);
    }

    public function testGetInfoNotCurrentAccount(FunctionalTester $I) {
        $I->amAuthenticated();

        $this->route->get(10);
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}
