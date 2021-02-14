<?php
declare(strict_types=1);

namespace common\tests\unit\notifications;

use Codeception\Test\Unit;
use common\models\Account;
use common\notifications\AccountEditNotification;

/**
 * @covers \common\notifications\AccountEditNotification
 */
class AccountEditNotificationTest extends Unit {

    public function testGetPayloads(): void {
        $account = new Account();
        $account->id = 123;
        $account->username = 'mock-username';
        $account->uuid = 'afc8dc7a-4bbf-4d3a-8699-68890088cf84';
        $account->email = 'mock@ely.by';
        $account->lang = 'en';
        $account->status = Account::STATUS_ACTIVE;
        $account->created_at = 1531008814;
        $changedAttributes = [
            'username' => 'old-username',
            'uuid' => 'e05d33e9-ff91-4d26-9f5c-8250f802a87a',
            'email' => 'old-email@ely.by',
            'status' => 0,
        ];

        $notification = new AccountEditNotification($account, $changedAttributes);
        $this->assertSame('account.edit', $notification::getType());
        $this->assertSame([
            'id' => 123,
            'uuid' => 'afc8dc7a-4bbf-4d3a-8699-68890088cf84',
            'username' => 'mock-username',
            'email' => 'mock@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'isDeleted' => false,
            'registered' => '2018-07-08T00:13:34+00:00',
            'changedAttributes' => $changedAttributes,
        ], $notification->getPayloads());
    }

}
