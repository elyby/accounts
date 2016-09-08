<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class SessionServerRoute extends BasePage {

    public function join($params) {
        $this->route = '/minecraft/session/join';
        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function joinLegacy(array $params) {
        $this->route = '/minecraft/session/legacy/join';
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function hasJoined(array $params) {
        $this->route = '/minecraft/session/hasJoined';
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function hasJoinedLegacy(array $params) {
        $this->route = '/minecraft/session/legacy/hasJoined';
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function profile($profileUuid) {
        $this->route = '/minecraft/session/profile/' . $profileUuid;
        $this->actor->sendGET($this->getUrl());
    }

}
