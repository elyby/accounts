<?php
namespace codeception\api\functional;

use OTPHP\TOTP;
use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\FunctionalTester;

class ForgotPasswordCest {

    /**
     * @var AuthenticationRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AuthenticationRoute($I);
    }

    public function testWrongInput(FunctionalTester $I) {
        $I->wantTo('see reaction on invalid input');

        $this->route->forgotPassword();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.login_required',
            ],
        ]);

        $this->route->forgotPassword('becauseimbatman!');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.login_not_exist',
            ],
        ]);

        $this->route->forgotPassword('AccountWithEnabledOtp');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_required',
            ],
        ]);

        $this->route->forgotPassword('AccountWithEnabledOtp');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_required',
            ],
        ]);

        $this->route->forgotPassword('AccountWithEnabledOtp', '123456');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_incorrect',
            ],
        ]);
    }

    public function testForgotPasswordByEmail(FunctionalTester $I) {
        $I->wantTo('create new password recover request by passing email');
        $this->route->forgotPassword('admin@ely.by');
        $this->assertSuccessResponse($I, false);
    }

    public function testForgotPasswordByUsername(FunctionalTester $I) {
        $I->wantTo('create new password recover request by passing username');
        $this->route->forgotPassword('Admin');
        $this->assertSuccessResponse($I, true);
    }

    public function testForgotPasswordByAccountWithOtp(FunctionalTester $I) {
        $I->wantTo('create new password recover request by passing username and otp token');
        $totp = new TOTP(null, 'secret-secret-secret');
        $this->route->forgotPassword('AccountWithEnabledOtp', $totp->now());
        $this->assertSuccessResponse($I, true);
    }

    public function testDataForFrequencyError(FunctionalTester $I) {
        $I->wantTo('get info about time to repeat recover password request');
        $this->route->forgotPassword('Notch');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.recently_sent_message',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
    }

    /**
     * @param FunctionalTester $I
     */
    private function assertSuccessResponse(FunctionalTester $I, bool $expectEmailMask = false): void {
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
        if ($expectEmailMask) {
            $I->canSeeResponseJsonMatchesJsonPath('$.data.emailMask');
        }
    }

}
