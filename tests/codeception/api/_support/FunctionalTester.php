<?php
namespace tests\codeception\api;

use api\models\AccountIdentity;
use Codeception\Actor;
use InvalidArgumentException;
use Yii;

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

    public function amAuthenticated(string $asUsername = 'admin') {
        /** @var AccountIdentity $account */
        $account = AccountIdentity::findOne(['username' => $asUsername]);
        if ($account === null) {
            throw new InvalidArgumentException("Cannot find account for username \"$asUsername\"");
        }

        $result = Yii::$app->user->login($account);
        $this->amBearerAuthenticated($result->getJwt());
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
