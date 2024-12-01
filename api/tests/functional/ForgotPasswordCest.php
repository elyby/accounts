<?php
namespace api\tests\functional;

use api\tests\_pages\AuthenticationRoute;
use api\tests\FunctionalTester;

class ForgotPasswordCest {

    private AuthenticationRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AuthenticationRoute($I);
    }

    public function testWrongInput(FunctionalTester $I): void {
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
    }

    public function testForgotPasswordByEmail(FunctionalTester $I): void {
        $I->wantTo('create new password recover request by passing email');
        $this->route->forgotPassword('admin@ely.by');
        $this->assertSuccessResponse($I, false);
    }

    public function testForgotPasswordByUsername(FunctionalTester $I): void {
        $I->wantTo('create new password recover request by passing username');
        $this->route->forgotPassword('Admin');
        $this->assertSuccessResponse($I, true);
    }

    public function testDataForFrequencyError(FunctionalTester $I): void {
        $I->wantTo('get info about time to repeat recover password request');
        $this->route->forgotPassword('Notch');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.recently_sent_message',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
    }

    /**
     * @param FunctionalTester $I
     */
    private function assertSuccessResponse(FunctionalTester $I, bool $expectEmailMask = false): void {
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        if ($expectEmailMask) {
            $I->canSeeResponseJsonMatchesJsonPath('$.data.emailMask');
        }
    }

}
