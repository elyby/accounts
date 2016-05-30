<?php
namespace codeception\api\functional;

use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\FunctionalTester;

class RefreshTokenCest {

    public function testRefreshInvalidToken(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('get error.refresh_token_not_exist if passed token is invalid');
        $route->refreshToken('invalid-token');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'refresh_token' => 'error.refresh_token_not_exist',
            ],
        ]);
    }

    public function testRefreshToken(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('get new access_token by my refresh_token');
        $route->refreshToken('SOutIr6Seeaii3uqMVy3Wan8sKFVFrNz');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeAuthCredentials(false);
    }

}
