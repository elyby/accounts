<?php
namespace tests\codeception\api\functional;

use Codeception\Specify;
use tests\codeception\api\_pages\SignupRoute;
use tests\codeception\api\FunctionalTester;

class NewAccountActivationCest {

    public function testSuccess(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('ensure that signup works');
        $route->sendNewMessage('achristiansen@gmail.com');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => true]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

}
