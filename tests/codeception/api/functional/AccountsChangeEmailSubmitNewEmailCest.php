<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangeEmailSubmitNewEmailCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testSubmitNewEmail(FunctionalTester $I) {
        $I->wantTo('submit new email');
        $I->loggedInAsActiveAccount('ILLIMUNATI', 'password_0');

        $this->route->changeEmailSubmitNewEmail('H27HBDCHHAG2HGHGHS', 'my-new-email@ely.by');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
