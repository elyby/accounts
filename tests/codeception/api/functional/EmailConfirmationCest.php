<?php
namespace tests\codeception\api;

use tests\codeception\api\_pages\SignupRoute;

class EmailConfirmationCest {

    public function testLoginEmailOrUsername(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('see error.key_is_required expected if key is not set');
        $route->confirm();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'key' => 'error.key_is_required',
            ],
        ]);

        $I->wantTo('see error.key_not_exists expected if key not exists in database');
        $route->confirm('not-exists-key');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'key' => 'error.key_not_exists',
            ],
        ]);
    }

    public function testLoginByEmailCorrect(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('confirm my email using correct activation key');
        $route->confirm('HABGCABHJ1234HBHVD');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');
    }

}
