<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;

class ChangeEmailConfirmNewEmailCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testConfirmNewEmail(FunctionalTester $I): void {
        $I->wantTo('change my email and get changed value');
        $I->amAuthenticated('CrafterGameplays');

        $this->route->changeEmail(8, 'H28HBDCHHAG2HGHGHS');
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
