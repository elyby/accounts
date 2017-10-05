<?php
namespace tests\codeception\api\_pages;

class InternalRoute extends BasePage {

    public function info(string $param, string $value) {
        $this->getActor()->sendGET('/internal/accounts/info', [$param => $value]);
    }

}
