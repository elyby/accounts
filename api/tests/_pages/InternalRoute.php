<?php
namespace api\tests\_pages;

class InternalRoute extends BasePage {

    public function info(string $param, string $value): void {
        $this->getActor()->sendGET('/api/internal/accounts/info', [$param => $value]);
    }

}
