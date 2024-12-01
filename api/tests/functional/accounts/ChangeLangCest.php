<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;

class ChangeLangCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testSubmitNewEmail(FunctionalTester $I): void {
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
