<?php
namespace codeception\api\functional;

use tests\codeception\api\_pages\OptionsRoute;
use tests\codeception\api\FunctionalTester;

class OptionsCest {

    /**
     * @var OptionsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OptionsRoute($I);
    }

    public function testRecaptchaPublicKey(FunctionalTester $I) {
        $I->wantTo('Get recaptcha public key');

        $this->route->get();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'reCaptchaPublicKey' => 'public-key',
        ]);
    }

}
