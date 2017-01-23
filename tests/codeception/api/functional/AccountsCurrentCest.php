<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsCurrentCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testCurrent(FunctionalTester $I) {
        $I->loggedInAsActiveAccount();

        $this->route->current();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'username' => 'Admin',
            'email' => 'admin@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => false,
            'isOtpEnabled' => false,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testExpiredCurrent(FunctionalTester $I) {
        // Устанавливаем заведомо истёкший токен
        $I->amBearerAuthenticated(
            'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpYXQiO' .
            'jE0NjQ2Mjc1NDUsImV4cCI6MTQ2NDYzMTE0NSwianRpIjoxfQ.9c1mm0BK-cuW1qh15F12s2Fh37IN43YeeZeU4DFtlrE'
        );

        $this->route->current();
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Token expired',
            'code' => 0,
            'status' => 401,
        ]);
    }

}
