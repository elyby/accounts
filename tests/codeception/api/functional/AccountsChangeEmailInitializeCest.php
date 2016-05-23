<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
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

    public function testChangeEmailWithOldPasswordStrategy(FunctionalTester $I) {
        $I->wantTo('see, that account use old account password hash strategy');
        $I->loggedInAsActiveAccount('AccWithOldPassword', '12345678');

        $this->route->changeEmailInitialize('password_0');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.old_hash_strategy',
            ],
        ]);
    }

}
