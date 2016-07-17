<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangeEmailInitializeCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testChangeEmailInitialize(FunctionalTester $I) {
        $I->wantTo('send current email confirmation');
        $I->loggedInAsActiveAccount();

        $this->route->changeEmailInitialize('password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testChangeEmailInitializeFrequencyError(FunctionalTester $I) {
        $I->wantTo('see change email request frequency error');
        $I->loggedInAsActiveAccount('ILLIMUNATI', 'password_0');

        $this->route->changeEmailInitialize('password_0');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.recently_sent_message',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
    }

}
