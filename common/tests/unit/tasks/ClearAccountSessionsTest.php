<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\Account;
use common\tasks\ClearAccountSessions;
use common\tests\fixtures;
use common\tests\unit\TestCase;
use yii\queue\Queue;

/**
 * @covers \common\tasks\ClearAccountSessions
 */
class ClearAccountSessionsTest extends TestCase {

    public function _fixtures(): array {
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
