<?php
namespace tests\codeception\api\functional;

use OTPHP\TOTP;
use tests\codeception\api\_pages\TwoFactorAuthRoute;
use tests\codeception\api\FunctionalTester;

class TwoFactorAuthEnableCest {

    /**
     * @var TwoFactorAuthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new TwoFactorAuthRoute($I);
    }

    public function testFails(FunctionalTester $I) {
        $I->amAuthenticated('AccountWithOtpSecret');

        $this->route->enable();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->enable('123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $I->amAuthenticated('AccountWithEnabledOtp');
        $this->route->enable('123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.otp_already_enabled',
            ],
        ]);
    }

    public function testSuccessEnable(FunctionalTester $I) {
        $I->amAuthenticated('AccountWithOtpSecret');
        $totp = new TOTP(null, 'some otp secret value');
        $this->route->enable($totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
