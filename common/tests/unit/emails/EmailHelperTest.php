<?php
namespace common\tests\unit\emails;

use common\emails\EmailHelper;
use common\tests\unit\TestCase;

class EmailHelperTest extends TestCase {

    public function testBuildTo(): void {
        $this->assertSame(['mock@ely.by' => 'username'], EmailHelper::buildTo('username', 'mock@ely.by'));
    }

}
