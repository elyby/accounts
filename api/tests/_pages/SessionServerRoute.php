<?php
namespace api\tests\_pages;

class SessionServerRoute extends BasePage {

    public function join($params) {
        $this->getActor()->sendPOST('/api/minecraft/session/join', $params);
    }

    public function joinLegacy(array $params) {
        $this->getActor()->sendGET('/api/minecraft/session/legacy/join', $params);
    }

    public function hasJoined(array $params) {
        $this->getActor()->sendGET('/api/minecraft/session/hasJoined', $params);
    }

    public function hasJoinedLegacy(array $params) {
        $this->getActor()->sendGET('/api/minecraft/session/legacy/hasJoined', $params);
    }

    public function profile($profileUuid) {
        $this->getActor()->sendGET("/api/minecraft/session/profile/{$profileUuid}");
    }

}
