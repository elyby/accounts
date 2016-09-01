<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AuthserverRoute extends BasePage {

    public function authenticate($params) {
        $this->route = ['authserver/authentication/authenticate'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function refresh($params) {
        $this->route = ['authserver/authentication/refresh'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function validate($params) {
        $this->route = ['authserver/authentication/validate'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function invalidate($params) {
        $this->route = ['authserver/authentication/invalidate'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function signout($params) {
        $this->route = ['authserver/authentication/signout'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

}
