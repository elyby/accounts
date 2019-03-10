<?php
namespace api\tests\_pages;

use api\tests\FunctionalTester;

class BasePage {

    /**
     * @var FunctionalTester
     */
    private $actor;

    public function __construct(FunctionalTester $I) {
        $this->actor = $I;
    }

    public function getActor(): FunctionalTester {
        return $this->actor;
    }

}
