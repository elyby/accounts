<?php
namespace codeception\common\unit\models;

use Codeception\Specify;
use common\models\AccountSession;
use tests\codeception\common\unit\TestCase;

class AccountSessionTest extends TestCase {
    use Specify;

    public function testGenerateRefreshToken() {
        $this->specify('method call will set refresh_token value', function() {
            $model = new AccountSession();
            $model->generateRefreshToken();
            expect($model->refresh_token)->notNull();
        });
    }

    public function testSetIp() {
        $this->specify('method should convert passed ip string to long', function() {
            $model = new AccountSession();
            $model->setIp('127.0.0.1');
            expect($model->last_used_ip)->equals(2130706433);
        });
    }

    public function testGetReadableIp() {
        $this->specify('method should convert stored ip long into readable ip string', function() {
            $model = new AccountSession();
            $model->last_used_ip = 2130706433;
            expect($model->getReadableIp())->equals('127.0.0.1');
        });
    }

}
