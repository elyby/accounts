<?php
namespace codeception\api\functional;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\FunctionalTester;

class RecoverPasswordCest {

    public function testDataForFrequencyError(FunctionalTester $I) {
        $authRoute = new AuthenticationRoute($I);

        $I->wantTo('change my account password, using key from email');
        $authRoute->recoverPassword('H24HBDCHHAG2HGHGHS', '12345678', '12345678');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');

        $I->wantTo('ensure, that jwt token is valid');
        $jwt = $I->grabDataFromResponseByJsonPath('$.jwt')[0];
        $I->amBearerAuthenticated($jwt);
        $accountRoute = new AccountsRoute($I);
        $accountRoute->current();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->notLoggedIn();

        $I->wantTo('check, that password is really changed');
        $authRoute->login('Notch', '12345678');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
