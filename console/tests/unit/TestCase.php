<?php
declare(strict_types=1);

namespace console\tests\unit;

use AllowDynamicProperties;
use Codeception\Test\Unit;
use common\tests\helpers\ExtendedPHPMock;
use console\tests\UnitTester;

/**
 * @property UnitTester $tester
 */
#[AllowDynamicProperties]
class TestCase extends Unit {
    use ExtendedPHPMock;

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
