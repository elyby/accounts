<?php
namespace tests\codeception\api\functional\accounts;

use OTPHP\TOTP;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class EnableTwoFactorAuthCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testFails(FunctionalTester $I) {
        $accountId = $I->amAuthenticated('AccountWithOtpSecret');

        $this->route->enableTwoFactorAuth($accountId);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_required',
                'password' => 'error.password_required',
            ],
        ]);

        $this->route->enableTwoFactorAuth($accountId, '123456', 'invalid_password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_incorrect',
                'password' => 'error.password_incorrect',
            ],
        ]);

        $accountId = $I->amAuthenticated('AccountWithEnabledOtp');
        $this->route->enableTwoFactorAuth($accountId, '123456', 'invalid_password');
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
        $this->route->enableTwoFactorAuth($accountId, $totp->now(), 'password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
