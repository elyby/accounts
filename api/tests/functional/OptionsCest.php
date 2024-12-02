<?php
namespace api\tests\functional;

use api\tests\_pages\OptionsRoute;
use api\tests\FunctionalTester;

class OptionsCest {

    private OptionsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new OptionsRoute($I);
    }

    public function testRecaptchaPublicKey(FunctionalTester $I): void {
        $I->wantTo('Get recaptcha public key');

        $this->route->get();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'reCaptchaPublicKey' => 'public-key',
        ]);
    }

}
