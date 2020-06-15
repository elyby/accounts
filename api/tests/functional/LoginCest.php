<?php
declare(strict_types=1);

namespace api\tests\functional;

use api\tests\_pages\AuthenticationRoute;
use api\tests\FunctionalTester;
use OTPHP\TOTP;

// TODO: very outdated tests. Need to rewrite
class LoginCest {

    public function testLoginEmailOrUsername(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('see error.login_required expected if login is not set');
        $route->login();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.login_required',
            ],
        ]);

        $I->wantTo('see error.login_not_exist expected if username not exists in database');
        $route->login('non-exist-username');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.login_not_exist',
            ],
        ]);

        $I->wantTo('see error.login_not_exist expected if email not exists in database');
        $route->login('not-exist@user.com');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.login_not_exist',
            ],
        ]);

        $I->wantTo('see error.account_not_activated expected if credentials are valid, but account is not activated');
        $route->login('howe.garnett', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.account_not_activated',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.email');

        $I->wantTo('don\'t see errors on login field if username is correct and exists in database');
        $route->login('Admin');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.login');

        $I->wantTo('don\'t see errors on login field if email is correct and exists in database');
        $route->login('admin@ely.by');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.login');
    }

    public function testLoginPassword(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('see password doesn\'t have errors if email or username not set');
        $route->login();
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.password');

        $I->wantTo('see password doesn\'t have errors if username not exists in database');
        $route->login('non-exist-username', 'random-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.password');

        $I->wantTo('see password doesn\'t has errors if email not exists in database');
        $route->login('not-exist@user.com', 'random-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.password');

        $I->wantTo('see error.password_incorrect if email correct, but password wrong');
        $route->login('admin@ely.by', 'wrong-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'password' => 'error.password_incorrect',
            ],
        ]);

        $I->wantTo('see error.password_incorrect if username correct, but password wrong');
        $route->login('Admin', 'wrong-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'password' => 'error.password_incorrect',
            ],
        ]);
    }

    public function testLoginToken(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('see totp don\'t have errors if email, username or totp not set');
        $route->login();
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.totp');

        $I->wantTo('see totp don\'t have errors if username not exists in database');
        $route->login('non-exist-username', 'random-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.totp');

        $I->wantTo('see totp don\'t has errors if email not exists in database');
        $route->login('not-exist@user.com', 'random-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.totp');

        $I->wantTo('see totp don\'t has errors if email correct, but password wrong');
        $route->login('not-exist@user.com', 'random-password');
        $I->canSeeResponseContainsJson([
            'success' => false,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.totp');

        $I->wantTo('see error.totp_required if username and password correct, but account have enable otp');
        $route->login('AccountWithEnabledOtp', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_required',
            ],
        ]);

        $I->wantTo('see error.totp_incorrect if username and password correct, but totp wrong');
        $route->login('AccountWithEnabledOtp', 'password_0', '123456');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'totp' => 'error.totp_incorrect',
            ],
        ]);
    }

    public function testLoginByUsernameCorrect(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account using correct username and password');
        $route->login('Admin', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(false);
    }

    public function testLoginByEmailCorrect(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account using correct email and password');
        $route->login('admin@ely.by', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(false);
    }

    public function testLoginInAccWithPasswordMethod(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account with old password hash function using correct username and password');
        $route->login('AccWithOldPassword', '12345678');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(false);
    }

    public function testLoginByEmailWithRemember(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account using correct data and get refresh_token');
        $route->login('admin@ely.by', 'password_0', true);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(true);
    }

    public function testLoginByAccountWithOtp(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account with enabled otp');
        $route->login('AccountWithEnabledOtp', 'password_0', (TOTP::create('BBBB'))->now());
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(false);
    }

    public function testLoginIntoDeletedAccount(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into account that marked for deleting');
        $route->login('DeletedAccount', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testLoginIntoBannedAccount(FunctionalTester $I) {
        $route = new AuthenticationRoute($I);

        $I->wantTo('login into banned account');
        $route->login('Banned', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'login' => 'error.account_banned',
            ],
        ]);
    }

}
