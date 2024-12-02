<?php
namespace api\tests\_pages;

class MojangApiRoute extends BasePage {

    public function usernameToUuid($username, $at = null): void {
        $params = $at === null ? [] : ['at' => $at];
        $this->getActor()->sendGET("/api/mojang/profiles/{$username}", $params);
    }

    public function usernamesByUuid($uuid): void {
        $this->getActor()->sendGET("/api/mojang/profiles/{$uuid}/names");
    }

}
