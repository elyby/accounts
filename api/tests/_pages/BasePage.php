<?php
namespace api\tests\_pages;

use api\tests\FunctionalTester;

class BasePage {

    public function __construct(
        private readonly FunctionalTester $actor,
    ) {
    }

    public function getActor(): FunctionalTester {
        return $this->actor;
    }

}
