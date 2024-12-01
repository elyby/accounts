<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;
use OTPHP\TOTP;

class DisableTwoFactorAuthCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testFails(FunctionalTester $I): void {
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

    public function testSuccessEnable(FunctionalTester $I): void {
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
