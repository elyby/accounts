<?php
namespace tests\codeception\api\_pages;

use tests\codeception\api\FunctionalTester;

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
