<?php
namespace tests\codeception\api;

use Codeception\Actor;
use InvalidArgumentException;
use tests\codeception\api\_pages\AuthenticationRoute;

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
class FunctionalTester extends Actor {
    use _generated\FunctionalTesterActions;

    public function loggedInAsActiveAccount($login = null, $password = null) {
        $route = new AuthenticationRoute($this);
        if ($login === null) {
            $route->login('Admin', 'password_0');
        } elseif ($login !== null && $password !== null) {
            $route->login($login, $password);
        } else {
            throw new InvalidArgumentException('login and password should be presented both.');
        }

        $this->canSeeResponseIsJson();
        $this->canSeeResponseJsonMatchesJsonPath('$.jwt');
        $jwt = $this->grabDataFromResponseByJsonPath('$.jwt')[0];
        $this->amBearerAuthenticated($jwt);
    }

    public function notLoggedIn() {
        $this->haveHttpHeader('Authorization', null);
    }

}
