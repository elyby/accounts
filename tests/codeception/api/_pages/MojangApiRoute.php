<?php
namespace tests\codeception\api\_pages;

class MojangApiRoute extends BasePage {

    public function usernameToUuid($username, $at = null) {
        $params = $at === null ? [] : ['at' => $at];
        $this->getActor()->sendGET("/mojang/profiles/{$username}", $params);
    }

    public function usernamesByUuid($uuid) {
        $this->getActor()->sendGET("/mojang/profiles/{$uuid}/names");
    }

    public function uuidsByUsernames($uuids) {
        $this->getActor()->sendPOST('/mojang/profiles', $uuids);
    }

}
