<?php
namespace tests\codeception\api\unit;

use Mockery;

class TestCase extends \Codeception\Test\Unit {

    /**
     * @var \tests\codeception\api\UnitTester
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
    public function _fixtures() {
        return [];
    }

}
