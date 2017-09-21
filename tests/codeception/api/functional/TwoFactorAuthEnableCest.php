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
        $accountId = $I->amAuthenticated('AccountWithOtpSecret');

        $this->route->enable($accountId);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->enable($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $accountId = $I->amAuthenticated('AccountWithEnabledOtp');
        $this->route->enable($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.otp_already_enabled',
            ],
        ]);
    }

    public function testSuccessEnable(FunctionalTester $I) {
        $accountId = $I->amAuthenticated('AccountWithOtpSecret');
        $totp = TOTP::create('AAAA');
        $this->route->enable($accountId, $totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
