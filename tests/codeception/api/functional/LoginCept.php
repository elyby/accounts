<?php
use tests\codeception\api\_pages\LoginRoute;
use tests\codeception\api\FunctionalTester;

/* @var $scenario Codeception\Scenario */

$I = new FunctionalTester($scenario);
$I->wantTo('ensure login page works');

$loginPage = LoginRoute::openBy($I);

$I->amGoingTo('submit login form with no data');
$loginPage->login('', '');
$I->expectTo('see validations errors');
$I->canSeeResponseContainsJson([
    'success' => false,
    'errors' => [
        'email' => [
            'error.email_required',
        ],
        'password' => [
            'error.password_required',
        ],
    ],
]);

/*
$I->amGoingTo('try to login with wrong credentials');
$I->expectTo('see validations errors');
$loginPage->login('', 'wrong');
$I->expectTo('see validations errors');
$I->see('Incorrect username or password.', '.help-block');

$I->amGoingTo('try to login with correct credentials');
$loginPage->login('erau', 'password_0');
$I->expectTo('see that user is logged');
$I->seeLink('Logout (erau)');
$I->dontSeeLink('Login');
$I->dontSeeLink('Signup');
*/
