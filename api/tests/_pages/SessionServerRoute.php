<?php
namespace api\tests\_pages;

class SessionServerRoute extends BasePage {

    public function join($params): void {
        $this->getActor()->sendPOST('/api/minecraft/session/join', $params);
    }

    public function joinLegacy(array $params): void {
        $this->getActor()->sendGET('/api/minecraft/session/legacy/join', $params);
    }

    public function hasJoined(array $params): void {
        $this->getActor()->sendGET('/api/minecraft/session/hasJoined', $params);
    }

    public function hasJoinedLegacy(array $params): void {
        $this->getActor()->sendGET('/api/minecraft/session/legacy/hasJoined', $params);
    }

    public function profile(string $profileUuid, bool $signed = false): void {
        $url = "/api/minecraft/session/profile/{$profileUuid}";
        if ($signed) {
            $url .= '?unsigned=false';
        }

        $this->getActor()->sendGET($url);
    }

}
