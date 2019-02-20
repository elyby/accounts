<?php
namespace api\tests\_pages;

class IdentityInfoRoute extends BasePage {

    public function info() {
        $this->getActor()->sendGET('/api/account/v1/info');
    }

}
