<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class SessionServerRoute extends BasePage {

    public function join($params) {
        $this->route = ['sessionserver/session/join'];
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function joinLegacy(array $params) {
        $this->route = ['sessionserver/session/join-legacy'];
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function hasJoined(array $params) {
        $this->route = ['sessionserver/session/has-joined'];
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function hasJoinedLegacy(array $params) {
        $this->route = ['sessionserver/session/has-joined-legacy'];
        $this->actor->sendGET($this->getUrl(), $params);
    }

}
