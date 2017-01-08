<?php
namespace tests\codeception\api;

use api\components\User\LoginResult;
use api\models\authentication\LoginForm;
use Codeception\Actor;
use InvalidArgumentException;

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
        $form = new LoginForm();
        if ($login === null && $password === null) {
            $form->login = 'Admin';
            $form->password = 'password_0';
        } elseif ($login !== null && $password !== null) {
            $form->login = $login;
            $form->password = $password;
        } else {
            throw new InvalidArgumentException('login and password should be presented both.');
        }

        $result = $form->login();
        $this->assertInstanceOf(LoginResult::class, $result);
        if ($result !== false) {
            $this->amBearerAuthenticated($result->getJwt());
        }
    }

    public function notLoggedIn() {
        $this->haveHttpHeader('Authorization', null);
    }

    public function canSeeAuthCredentials($expectRefresh = false) {
        $this->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $this->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        if ($expectRefresh) {
            $this->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
        } else {
            $this->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
        }
    }

}
