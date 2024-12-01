<?php
namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeUsernameForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\UsernameHistory;
use common\tasks\PullMojangUsername;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\UsernameHistoryFixture;

class ChangeUsernameFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'history' => UsernameHistoryFixture::class,
        ];
    }

    public function testPerformAction(): void {
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => 'my_new_nickname',
        ]);
        $this->assertTrue($model->performAction());
        $this->assertSame('my_new_nickname', Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(UsernameHistory::class, UsernameHistory::findOne(['username' => 'my_new_nickname']));
        /** @var PullMojangUsername $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(PullMojangUsername::class, $job);
        $this->assertSame($job->username, 'my_new_nickname');
    }

    public function testPerformActionWithTheSameUsername(): void {
        $account = $this->getAccount();
        $username = $account->username;
        $model = new ChangeUsernameForm($account, [
            'password' => 'password_0',
            'username' => $username,
        ]);
        $callTime = time();
        $this->assertTrue($model->performAction());
        $this->assertNull(UsernameHistory::findOne([
            'AND',
            'username' => $username,
            ['>=', 'applied_in', $callTime],
        ]), 'no new UsernameHistory record, if we don\'t change username');
        $this->assertNull($this->tester->grabLastQueuedJob());
    }

    public function testPerformActionWithChangeCase(): void {
        $newUsername = mb_strtoupper($this->tester->grabFixture('accounts', 'admin')['username']);
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => $newUsername,
        ]);
        $this->assertTrue($model->performAction());
        $this->assertSame($newUsername, Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(
            UsernameHistory::class,
            UsernameHistory::findOne(['username' => $newUsername]),
            'username should change, if we change case of some letters',
        );
        /** @var PullMojangUsername $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(PullMojangUsername::class, $job);
        $this->assertSame($job->username, $newUsername);
    }

    private function getAccount(): Account {
        return $this->tester->grabFixture('accounts', 'admin');
    }

    private function getAccountId() {
        return $this->getAccount()->id;
    }

}
