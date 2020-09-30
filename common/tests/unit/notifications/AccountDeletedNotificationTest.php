<?php
declare(strict_types=1);

namespace common\tests\unit\notifications;

use Codeception\Test\Unit;
use common\models\Account;
use common\notifications\AccountDeletedNotification;

/**
 * @covers \common\notifications\AccountDeletedNotification
 */
class AccountDeletedNotificationTest extends Unit {

    public function testGetPayloads(): void {
        $account = new Account();
        $account->id = 123;
        $account->username = 'mock-username';
        $account->uuid = 'afc8dc7a-4bbf-4d3a-8699-68890088cf84';
        $account->email = 'mock@ely.by';
        $account->created_at = 1531008814;
        $account->deleted_at = 1601501781;

        $notification = new AccountDeletedNotification($account);
        $this->assertSame('account.deletion', $notification::getType());
        $this->assertSame([
            'id' => 123,
            'uuid' => 'afc8dc7a-4bbf-4d3a-8699-68890088cf84',
            'username' => 'mock-username',
            'email' => 'mock@ely.by',
            'registered' => '2018-07-08T00:13:34+00:00',
            'deleted' => '2020-09-30T21:36:21+00:00',
        ], $notification->getPayloads());
    }

}
