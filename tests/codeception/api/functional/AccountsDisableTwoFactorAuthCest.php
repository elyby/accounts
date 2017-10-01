<?php
namespace tests\codeception\api\functional;

use OTPHP\TOTP;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsDisableTwoFactorAuthCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testFails(FunctionalTester $I) {
        $accountId = $I->amAuthenticated('AccountWithEnabledOtp');

        $this->route->disableTwoFactorAuth($accountId);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->disableTwoFactorAuth($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $accountId = $I->amAuthenticated('AccountWithOtpSecret');
        $this->route->disableTwoFactorAuth($accountId, '123456', 'invalid_password');
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
        $this->route->disableTwoFactorAuth($accountId, $totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
