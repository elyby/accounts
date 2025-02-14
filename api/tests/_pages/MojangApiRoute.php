<?php
namespace api\tests\_pages;

class MojangApiRoute extends BasePage {

    public function usernamesByUuid($uuid): void {
        $this->getActor()->sendGET("/api/mojang/profiles/{$uuid}/names");
    }

}
