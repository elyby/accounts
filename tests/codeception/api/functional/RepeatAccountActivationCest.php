<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\_pages\SignupRoute;
use tests\codeception\api\FunctionalTester;

class RepeatAccountActivationCest {

    public function testInvalidEmailOrAccountState(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('error.email_required on empty for submitting');
        $route->sendRepeatMessage();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_required',
            ],
        ]);

        $I->wantTo('error.email_not_found if email is not presented in db');
        $route->sendRepeatMessage('im-not@exists.net');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.email_not_found',
            ],
        ]);

        $I->wantTo('error.account_already_activated if passed email matches with already activated account');
        $route->sendRepeatMessage('admin@ely.by');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.account_already_activated',
            ],
        ]);

        $I->wantTo('error.recently_sent_message if last message was send too recently');
        $route->sendRepeatMessage('achristiansen@gmail.com');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'email' => 'error.recently_sent_message',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.data.canRepeatIn');
        $I->canSeeResponseJsonMatchesJsonPath('$.data.repeatFrequency');
    }

    public function testSuccess(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('successfully resend account activation message');
        $route->sendRepeatMessage('jon@ely.by');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => true]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

}
