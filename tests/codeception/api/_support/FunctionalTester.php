<?php
namespace tests\codeception\api;

use tests\codeception\api\_pages\LoginRoute;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \Codeception\Actor {
    use _generated\FunctionalTesterActions;

    public function loggedInAsActiveAccount() {
        $I = $this;
        $route = new LoginRoute($I);
        $route->login('Admin', 'password_0');
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.jwt');
        $jwt = $I->grabDataFromResponseByJsonPath('$.jwt')[0];
        $I->amBearerAuthenticated($jwt);
    }

    public function notLoggedIn() {
        $this->haveHttpHeader('Authorization', null);
    }

}
