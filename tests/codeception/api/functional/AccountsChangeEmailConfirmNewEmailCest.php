<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangeEmailConfirmNewEmailCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testConfirmNewEmail(FunctionalTester $I) {
        $I->wantTo('change my email and get changed value');
        $I->loggedInAsActiveAccount('CrafterGameplays', 'password_0');

        $this->route->changeEmailConfirmNewEmail('H28HBDCHHAG2HGHGHS');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'data' => [
                'email' => 'my-new-email@ely.by',
            ],
        ]);
    }

}
