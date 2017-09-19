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
        $accountId = $I->amAuthenticated('AccountWithEnabledOtp');

        $this->route->disable($accountId);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->disable($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $accountId = $I->amAuthenticated('AccountWithOtpSecret');
        $this->route->disable($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.otp_not_enabled',
            ],
        ]);
    }

    public function testSuccessEnable(FunctionalTester $I) {
        $accountId = $I->amAuthenticated('AccountWithEnabledOtp');
        $totp = TOTP::create('BBBB');
        $this->route->disable($accountId, $totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
