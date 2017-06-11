<?php
namespace tests\codeception\common\_support;

use Codeception\Module;
use Codeception\TestInterface;

class Mockery extends Module {

    /**
     * @var bool Run mockery expectations after test or not
     */
    private $assert_mocks = true;

    public function _before(TestInterface $test) {
        \Mockery::globalHelpers();
    }

    public function _after(TestInterface $test) {
        if ($this->assert_mocks) {
            \Mockery::close();
        } else {
            \Mockery::getContainer()->mockery_close();
            \Mockery::resetContainer();
        }
    }

    public function _failed(TestInterface $test, $fail) {
        $this->assert_mocks = false;
    }

}
