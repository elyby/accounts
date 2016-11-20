<?php
namespace codeception\common\unit\models;

use common\models\AccountSession;
use tests\codeception\common\unit\TestCase;

class AccountSessionTest extends TestCase {

    public function testGenerateRefreshToken() {
        $model = new AccountSession();
        $model->generateRefreshToken();
        $this->assertNotNull($model->refresh_token, 'method call will set refresh_token value');
    }

    public function testSetIp() {
        $model = new AccountSession();
        $model->setIp('127.0.0.1');
        $this->assertEquals(2130706433, $model->last_used_ip, 'method should convert passed ip string to long');
    }

    public function testGetReadableIp() {
        $model = new AccountSession();
        $model->last_used_ip = 2130706433;
        $this->assertEquals('127.0.0.1', $model->getReadableIp(), 'method should convert stored long into readable ip');
    }

}
