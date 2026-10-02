<?php
namespace api\tests\functional;

use api\tests\_pages\OptionsRoute;
use api\tests\FunctionalTester;

class OptionsCest {

    private OptionsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new OptionsRoute($I);
    }

    public function testCaptchaPublicParams(FunctionalTester $I): void {
        $I->wantTo('Get captcha public params');

        $this->route->get();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'reCaptchaPublicKey' => 'public-key',
            'captcha' => [
                'recaptcha' => [
                    'publicKey' => 'public-key',
                ],
                'yandex' => [
                    'publicKey' => 'yandex-public-key',
                ],
            ],
        ]);
    }

}
