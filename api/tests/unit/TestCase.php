<?php
declare(strict_types=1);

namespace api\tests\unit;

use Codeception\Test\Unit;
use Mockery;

class TestCase extends Unit {

    /**
     * @var \api\tests\UnitTester
     */
    protected $tester;

    protected function tearDown(): void {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * A list of fixtures that will be loaded before the test, but after the database is cleaned up
     *
     * @url http://codeception.com/docs/modules/Yii2#fixtures
     *
     * @return array
     */
    public function _fixtures(): array {
        return [];
    }

}
