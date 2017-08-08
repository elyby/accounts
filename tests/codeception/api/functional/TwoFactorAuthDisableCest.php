<?php
namespace tests\codeception\api\functional;

use OTPHP\TOTP;
use tests\codeception\api\_pages\TwoFactorAuthRoute;
use tests\codeception\api\FunctionalTester;

class TwoFactorAuthDisableCest {

    /**
     * @var TwoFactorAuthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new TwoFactorAuthRoute($I);
    }

    public function testFails(FunctionalTester $I) {
        $I->amAuthenticated('AccountWithEnabledOtp');

        $this->route->disable();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->disable('123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'token' => 'error.token_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $I->amAuthenticated('AccountWithOtpSecret');
        $this->route->disable('123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.otp_not_enabled',
            ],
        ]);
    }

    public function testSuccessEnable(FunctionalTester $I) {
        $I->amAuthenticated('AccountWithEnabledOtp');
        $totp = TOTP::create('BBBB');
        $this->route->disable($totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
