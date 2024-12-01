<?php
namespace api\tests\_pages;

class IdentityInfoRoute extends BasePage {

    public function info(): void {
        $this->getActor()->sendGET('/api/account/v1/info');
    }

}
