<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;

class AccountsChangeLangCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testSubmitNewEmail(FunctionalTester $I) {
        $I->wantTo('change my account language');
        $id = $I->amAuthenticated();

        $this->route->changeLanguage($id, 'ru');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
