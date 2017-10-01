<?php
namespace tests\codeception\api\_pages;

class IdentityInfoRoute extends BasePage {

    public function info() {
        $this->getActor()->sendGET('/account/v1/info');
    }

}
