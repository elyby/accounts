<?php
namespace api\tests\_pages;

class MojangApiRoute extends BasePage {

    public function usernameToUuid($username, $at = null) {
        $params = $at === null ? [] : ['at' => $at];
        $this->getActor()->sendGET("/api/mojang/profiles/{$username}", $params);
    }

    public function usernamesByUuid($uuid) {
        $this->getActor()->sendGET("/api/mojang/profiles/{$uuid}/names");
    }

}
