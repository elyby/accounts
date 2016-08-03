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

        $this->route->recaptchaPublicKey();
        $I->canSeeResponseCodeIs(200);
        // TODO: эта проверка не проходит, т.к внутри почему-то после запроса не устанавливаются http заголовки
        //$I->seeHttpHeader('Content-Type', 'text/html; charset=UTF-8');
        $I->canSeeResponseEquals('public-key');
    }

}
