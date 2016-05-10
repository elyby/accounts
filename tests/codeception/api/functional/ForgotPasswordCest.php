<?php
namespace codeception\api\functional;

use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\FunctionalTester;

class ForgotPasswordCest {

    public function testForgotPasswordByEmail(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('create new password recover request by passing email');
        $route->forgotPassword('admin@ely.by');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
    }

    public function testForgotPasswordByUsername(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('create new password recover request by passing username');
        $route->forgotPassword('Admin');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.emailMask');
    }

    public function testDataForFrequencyError(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('get info about time to repeat recover password request');
        $route->forgotPassword('Notch');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.email_frequency',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
    }

}
