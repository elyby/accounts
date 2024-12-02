<?php
declare(strict_types=1);

namespace api\tests\unit;

use Codeception\Test\Unit;
use common\tests\helpers\ExtendedPHPMock;

/**
 * @property \api\tests\UnitTester $tester
 */
class TestCase extends Unit {
    use ExtendedPHPMock;

    /**
     * A list of fixtures that will be loaded before the test, but after the database is cleaned up
     *
     * @url http://codeception.com/docs/modules/Yii2#fixtures
     *
     * @return array<string, class-string<\yii\test\Fixture>>
     */
    public function _fixtures(): array {
        return [];
    }

}
