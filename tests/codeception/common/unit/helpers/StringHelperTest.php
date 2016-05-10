<?php
namespace codeception\common\unit\helpers;

use common\helpers\StringHelper;

class StringHelperTest extends \PHPUnit_Framework_TestCase {

    public function testGetEmailMask() {
        $this->assertEquals('**@ely.by', StringHelper::getEmailMask('e@ely.by'));
        $this->assertEquals('e**@ely.by', StringHelper::getEmailMask('es@ely.by'));
        $this->assertEquals('e**i@ely.by', StringHelper::getEmailMask('eri@ely.by'));
        $this->assertEquals('er**ch@ely.by', StringHelper::getEmailMask('erickskrauch@ely.by'));
        $this->assertEquals('эр**уч@елу.бел', StringHelper::getEmailMask('эрикскрауч@елу.бел'));
    }

}
