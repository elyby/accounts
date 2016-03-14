<?php
namespace tests\codeception\api;

use tests\codeception\api\_pages\LoginRoute;

class LoginCest {

    public function testLoginEmailOrUsername(FunctionalTester $I) {
        $route = new LoginRoute($I);

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
        $route = new LoginRoute($I);

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

    public function testLoginByUsernameCorrect(FunctionalTester $I) {
        $route = new LoginRoute($I);

        $I->wantTo('login into account using correct username and password');
        $route->login('Admin', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

    public function testLoginByEmailCorrect(FunctionalTester $I) {
        $route = new LoginRoute($I);

        $I->wantTo('login into account using correct email and password');
        $route->login('admin@ely.by', 'password_0');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

    public function testLoginInAccWithPasswordMethod(FunctionalTester $I) {
        $route = new LoginRoute($I);

        $I->wantTo('login into account with old password hash function using correct username and password');
        $route->login('AccWithOldPassword', '12345678');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

}
