<?php
namespace tests\codeception\api\models\profile;

use api\models\AccountIdentity;
use api\models\profile\ChangeUsernameForm;
use Codeception\Specify;
use common\models\Account;
use common\models\UsernameHistory;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;
use Yii;

class ChangeUsernameFormTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'history' => UsernameHistoryFixture::class,
        ];
    }

    public function setUp() {
        parent::setUp();
        $account = AccountIdentity::findOne($this->getAccountId());
        Yii::$app->user->setIdentity($account);
    }

    public function testChange() {
        $model = new ChangeUsernameForm([
            'password' => 'password_0',
            'username' => 'my_new_nickname',
        ]);
        $this->assertTrue($model->change());
        $this->assertEquals('my_new_nickname', Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(UsernameHistory::class, UsernameHistory::findOne(['username' => 'my_new_nickname']));
    }

    public function testChangeWithoutChange() {
        $username = $this->tester->grabFixture('accounts', 'admin')['username'];
        $model = new ChangeUsernameForm([
            'password' => 'password_0',
            'username' => $username,
        ]);
        $callTime = time();
        $this->assertTrue($model->change());
        $this->assertNull(UsernameHistory::findOne([
            'AND',
            'username' => $username,
            ['>=', 'applied_in', $callTime],
        ]), 'no new UsernameHistory record, if we don\'t change nickname');
    }

    public function testChangeCase() {
        $newUsername = mb_strtoupper($this->tester->grabFixture('accounts', 'admin')['username']);
        $model = new ChangeUsernameForm([
            'password' => 'password_0',
            'username' => $newUsername,
        ]);
        $this->assertTrue($model->change());
        $this->assertEquals($newUsername, Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(
            UsernameHistory::class,
            UsernameHistory::findOne(['username' => $newUsername]),
            'username should change, if we change case of some letters'
        );
    }

    public function testValidateUsername() {
        $this->specify('error.username_not_available expected if username is already taken', function() {
            $model = new ChangeUsernameForm([
                'password' => 'password_0',
                'username' => 'Jon',
            ]);
            $model->validateUsername('username');
            expect($model->getErrors('username'))->equals(['error.username_not_available']);
        });

        $this->specify('error.username_not_available is NOT expected if username is already taken by CURRENT user', function() {
            $model = new ChangeUsernameForm([
                'password' => 'password_0',
                'username' => $this->tester->grabFixture('accounts', 'admin')['username'],
            ]);
            $model->validateUsername('username');
            expect($model->getErrors('username'))->isEmpty();
        });
    }

    public function testCreateTask() {
        $model = new ChangeUsernameForm();
        $model->createEventTask('1', 'test1', 'test');
        // TODO: у меня пока нет идей о том, чтобы это как-то успешно протестировать, увы
        // но по крайней мере можно убедиться, что оно не падает где-то на этом шаге
    }

    private function getAccountId() {
        return $this->tester->grabFixture('accounts', 'admin')['id'];
    }

}
