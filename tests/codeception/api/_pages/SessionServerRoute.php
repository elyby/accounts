<?php
namespace tests\codeception\api\_pages;

class SessionServerRoute extends BasePage {

    public function join($params) {
        $this->getActor()->sendPOST('/minecraft/session/join', $params);
    }

    public function joinLegacy(array $params) {
        $this->getActor()->sendGET('/minecraft/session/legacy/join', $params);
    }

    public function hasJoined(array $params) {
        $this->getActor()->sendGET('/minecraft/session/hasJoined', $params);
    }

    public function hasJoinedLegacy(array $params) {
        $this->getActor()->sendGET('/minecraft/session/legacy/hasJoined', $params);
    }

    public function profile($profileUuid) {
        $this->getActor()->sendGET("/minecraft/session/profile/{$profileUuid}");
    }

}
