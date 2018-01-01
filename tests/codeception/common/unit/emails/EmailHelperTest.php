<?php
namespace tests\codeception\common\unit\emails;

use common\emails\EmailHelper;
use tests\codeception\common\unit\TestCase;

class EmailHelperTest extends TestCase {

    public function testBuildTo() {
        $this->assertSame(['mock@ely.by' => 'username'], EmailHelper::buildTo('username', 'mock@ely.by'));
    }

}
