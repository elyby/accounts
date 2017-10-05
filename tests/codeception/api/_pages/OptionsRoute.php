<?php
namespace tests\codeception\api\_pages;

class OptionsRoute extends BasePage {

    public function get() {
        $this->getActor()->sendGET('/options');
    }

}
