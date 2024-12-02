<?php
namespace api\tests\functional;

use api\tests\_pages\AccountsRoute;
use api\tests\_pages\AuthenticationRoute;
use api\tests\FunctionalTester;

class RecoverPasswordCest {

    public function testDataForFrequencyError(FunctionalTester $I): void {
        $authRoute = new AuthenticationRoute($I);

        $I->wantTo('change my account password, using key from email');
        $authRoute->recoverPassword('H24HBDCHHAG2HGHGHS', '12345678', '12345678');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeAuthCredentials();

        $I->wantTo('ensure, that jwt token is valid');
        $jwt = $I->grabDataFromResponseByJsonPath('$.access_token')[0];
        $I->amBearerAuthenticated($jwt);
        $accountRoute = new AccountsRoute($I);
        $accountRoute->get(5);
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
