<?php
namespace api\tests\_pages;

class OptionsRoute extends BasePage {

    public function get(): void {
        $this->getActor()->sendGET('/api/options');
    }

}
