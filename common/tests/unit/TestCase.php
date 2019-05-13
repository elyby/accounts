<?php
declare(strict_types=1);

namespace common\tests\unit;

use Codeception\Test\Unit;
use Mockery;

class TestCase extends Unit {

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function tearDown() {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * Список фикстур, что будут загружены перед тестом, но после зачистки базы данных
     *
     * @url http://codeception.com/docs/modules/Yii2#fixtures
     *
     * @return array
     */
    public function _fixtures(): array {
        return [];
    }

}
