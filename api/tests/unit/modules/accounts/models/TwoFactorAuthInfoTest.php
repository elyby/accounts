<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\TwoFactorAuthInfo;
use api\tests\unit\TestCase;
use common\models\Account;

class TwoFactorAuthInfoTest extends TestCase {

    public function testGetCredentials() {
        $account = $this->createPartialMock(Account::class, ['save']);
        $account->method('save')->willReturn(true);

        $account->email = 'mock@email.com';
        $account->otp_secret = null;

        $model = new TwoFactorAuthInfo($account);

        $result = $model->getCredentials();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('qr', $result);
        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('secret', $result);
        $this->assertSame($account->otp_secret, $result['secret']);
        $this->assertSame(strtoupper($account->otp_secret), $account->otp_secret);
        $this->assertStringStartsWith('data:image/svg+xml,<?xml', $result['qr']);

        $previous = libxml_use_internal_errors(true);
        simplexml_load_string(base64_decode($result['qr']));
        libxml_use_internal_errors($previous);
        $this->assertEmpty(libxml_get_errors());

        $account = $this->createPartialMock(Account::class, ['save']);
        $account->method('save')->willReturn(true);

        $account->email = 'mock@email.com';
        $account->otp_secret = 'AAAA';

        $model = new TwoFactorAuthInfo($account);

        $result = $model->getCredentials();
        $this->assertSame('AAAA', $result['secret']);
    }

}
