<?php
namespace tests\codeception\common\unit;

class TestCase extends \Codeception\Test\Unit  {

    /**
     * @var \tests\codeception\common\UnitTester
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
