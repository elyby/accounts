<?php
namespace api\tests\_pages;

class AuthserverRoute extends BasePage {

    public function authenticate($params) {
        $this->getActor()->sendPOST('/api/authserver/authentication/authenticate', $params);
    }

    public function refresh($params) {
        $this->getActor()->sendPOST('/api/authserver/authentication/refresh', $params);
    }

    public function validate($params) {
        $this->getActor()->sendPOST('/api/authserver/authentication/validate', $params);
    }

    public function invalidate($params) {
        $this->getActor()->sendPOST('/api/authserver/authentication/invalidate', $params);
    }

    public function signout($params) {
        $this->getActor()->sendPOST('/api/authserver/authentication/signout', $params);
    }

}
