<?php
namespace tests\codeception\common\unit\emails;

use common\emails\EmailHelper;
use common\models\Account;
use tests\codeception\common\unit\TestCase;

class EmailHelperTest extends TestCase {

    public function testBuildTo() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class)->makePartial();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $this->assertEquals(['mock@ely.by' => 'mock-username'], EmailHelper::buildTo($account));
    }

}
