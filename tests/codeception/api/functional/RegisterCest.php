<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\_pages\SignupRoute;
use tests\codeception\api\FunctionalTester;

class RegisterCest {

    public function _after() {
        Account::deleteAll([
            'email' => 'erickskrauch@ely.by',
            'username' => 'ErickSkrauch',
        ]);
    }

    public function testIncorrectRegistration(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('get error.you_must_accept_rules if we don\'t accept rules');
        $route->register([
            'username' => 'ErickSkrauch',
            'email' => 'erickskrauch@ely.by',
            'password' => 'some_password',
            'rePassword' => 'some_password',
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'rulesAgreement' => 'error.you_must_accept_rules',
            ],
        ]);

        $I->wantTo('don\'t see error.you_must_accept_rules if we accept rules');
        $route->register([
            'rulesAgreement' => true,
        ]);
        $I->cantSeeResponseContainsJson([
            'errors' => [
                'rulesAgreement' => 'error.you_must_accept_rules',
            ],
        ]);

        $I->wantTo('see error.username_required if username is not set');
        $route->register([
            'username' => '',
            'email' => '',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'username' => 'error.username_required',
            ],
        ]);

        $I->wantTo('don\'t see error.username_required if username is not set');
        $route->register([
            'username' => 'valid_nickname',
            'email' => '',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->cantSeeResponseContainsJson([
            'errors' => [
                'username' => 'error.username_required',
            ],
        ]);

        $I->wantTo('see error.email_required if email is not set');
        $route->register([
            'username' => 'valid_nickname',
            'email' => '',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_required',
            ],
        ]);

        $I->wantTo('see error.email_invalid if email is set, but invalid');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'invalid@email',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_invalid',
            ],
        ]);

        $I->wantTo('see error.email_invalid if email is set, valid, but domain doesn\'t exist or don\'t have mx record');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'invalid@govnomail.com',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_invalid',
            ],
        ]);

        $I->wantTo('see error.email_not_available if email is set, fully valid, but not available for registration');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'admin@ely.by',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_not_available',
            ],
        ]);

        $I->wantTo('don\'t see errors on email if all valid');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'erickskrauch@ely.by',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.email');

        $I->wantTo('see error.password_required if password is not set');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'erickskrauch@ely.by',
            'password' => '',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'password' => 'error.password_required',
            ],
        ]);

        $I->wantTo('see error.password_too_short before it will be compared with rePassword');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'correct-email@ely.by',
            'password' => 'short',
            'rePassword' => 'password',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'password' => 'error.password_too_short',
            ],
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.rePassword');

        $I->wantTo('see error.rePassword_required if password valid and rePassword not set');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'correct-email@ely.by',
            'password' => 'valid-password',
            'rePassword' => '',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'rePassword' => 'error.rePassword_required',
            ],
        ]);

        $I->wantTo('see error.rePassword_does_not_match if password valid and rePassword donen\'t match it');
        $route->register([
            'username' => 'valid_nickname',
            'email' => 'correct-email@ely.by',
            'password' => 'valid-password',
            'rePassword' => 'password',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'rePassword' => 'error.rePassword_does_not_match',
            ],
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors.password');
    }

    public function testUserCorrectRegistration(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('ensure that signup works');
        $route->register([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
            'password' => 'some_password',
            'rePassword' => 'some_password',
            'rulesAgreement' => true,
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => true]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

}
