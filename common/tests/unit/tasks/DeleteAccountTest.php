<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\Account;
use common\tasks\DeleteAccount;
use common\tests\fixtures;
use common\tests\unit\TestCase;
use yii\queue\Queue;

/**
 * @covers \common\tasks\DeleteAccount
 */
class DeleteAccountTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => fixtures\AccountFixture::class,
            'authSessions' => fixtures\AccountSessionFixture::class,
            'emailActivations' => fixtures\EmailActivationFixture::class,
            'minecraftAccessKeys' => fixtures\MinecraftAccessKeyFixture::class,
            'usernamesHistory' => fixtures\UsernameHistoryFixture::class,
            'oauthClients' => fixtures\OauthClientFixture::class,
            'oauthSessions' => fixtures\OauthSessionFixture::class,
        ];
    }

    public function testExecute() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $account->status = Account::STATUS_DELETED;
        $account->deleted_at = time() - 60 * 60 * 24 * 7;
        $account->save();

        $task = new DeleteAccount($account->id);
        $task->execute($this->createMock(Queue::class));
        $this->assertEmpty($account->emailActivations);
        $this->assertEmpty($account->sessions);
        $this->assertEmpty($account->minecraftAccessKeys);
        $this->assertEmpty($account->oauthSessions);
        $this->assertEmpty($account->usernameHistory);
        $this->assertEmpty($account->oauthClients);
        $this->assertFalse($account->refresh());
    }

    /**
     * When a user restores his account back, the task doesn't removed
     * @throws \Throwable
     */
    public function testExecuteOnNotDeletedAccount() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        // By default, this account is active

        $task = new DeleteAccount($account->id);
        $task->execute($this->createMock(Queue::class));
        $this->assertNotEmpty($account->emailActivations);
        $this->assertNotEmpty($account->sessions);
        $this->assertNotEmpty($account->minecraftAccessKeys);
        $this->assertNotEmpty($account->oauthSessions);
        $this->assertNotEmpty($account->usernameHistory);
        $this->assertNotEmpty($account->oauthClients);
        $this->assertTrue($account->refresh());
    }

    /**
     * User also might delete his account, restore it and delete again.
     * For each deletion the job will be created, so assert, that job for restored deleting will not work
     * @throws \Throwable
     */
    public function testExecuteOnDeletedAccountWhichWasRestoredAndThenDeletedAgain() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $account->status = Account::STATUS_DELETED;
        $account->deleted_at = time() - 60 * 60 * 24 * 2;
        $account->save();

        $task = new DeleteAccount($account->id);
        $task->execute($this->createMock(Queue::class));
        $this->assertNotEmpty($account->emailActivations);
        $this->assertNotEmpty($account->sessions);
        $this->assertNotEmpty($account->minecraftAccessKeys);
        $this->assertNotEmpty($account->oauthSessions);
        $this->assertNotEmpty($account->usernameHistory);
        $this->assertNotEmpty($account->oauthClients);
        $this->assertTrue($account->refresh());
    }

}
