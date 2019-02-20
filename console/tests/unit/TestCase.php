<?php
declare(strict_types=1);

namespace console\tests\unit;

use Codeception\Test\Unit;
use Mockery;

/**
 * @property \console\tests\UnitTester $tester
 */
class TestCase extends Unit {

    protected function tearDown(): void {
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
