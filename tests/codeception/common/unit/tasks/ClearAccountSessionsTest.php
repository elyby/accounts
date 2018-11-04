<?php
declare(strict_types=1);

namespace tests\codeception\common\unit\tasks;

use common\models\Account;
use common\tasks\ClearAccountSessions;
use tests\codeception\common\fixtures;
use tests\codeception\common\unit\TestCase;
use yii\queue\Queue;

/**
 * @covers \common\tasks\ClearAccountSessions
 */
class ClearAccountSessionsTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => fixtures\AccountFixture::class,
            'oauthSessions' => fixtures\OauthSessionFixture::class,
            'minecraftAccessKeys' => fixtures\MinecraftAccessKeyFixture::class,
            'authSessions' => fixtures\AccountSessionFixture::class,
        ];
    }

    public function testCreateFromAccount() {
        $account = new Account();
        $account->id = 123;
        $task = ClearAccountSessions::createFromAccount($account);
        $this->assertSame(123, $task->accountId);
    }

    public function testExecute() {
        /** @var \common\models\Account $bannedAccount */
        $bannedAccount = $this->tester->grabFixture('accounts', 'banned-account');
        $task = new ClearAccountSessions();
        $task->accountId = $bannedAccount->id;
        $task->execute(mock(Queue::class));
        $this->assertEmpty($bannedAccount->sessions);
        $this->assertEmpty($bannedAccount->minecraftAccessKeys);
        $this->assertEmpty($bannedAccount->oauthSessions);
    }

}
