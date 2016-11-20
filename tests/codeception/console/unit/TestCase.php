<?php
namespace tests\codeception\console\unit;

use Codeception\Test\Unit;

class TestCase extends Unit {

    /**
     * @var \tests\codeception\console\UnitTester
     */
    protected $tester;

    /**
     * Список фикстур, что будут загружены перед тестом, но после зачистки базы данных
     *
     * @url http://codeception.com/docs/modules/Yii2#fixtures
     *
     * @return array
     */
    public function _fixtures() {
        return [];
    }

}
