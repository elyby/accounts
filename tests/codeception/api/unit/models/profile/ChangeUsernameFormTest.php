<?php
namespace tests\codeception\api\models\profile;

use api\models\profile\ChangeUsernameForm;
use Codeception\Specify;
use common\models\Account;
use common\models\UsernameHistory;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;

/**
 * @property AccountFixture $accounts
 */
class ChangeUsernameFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'history' => UsernameHistoryFixture::class,
        ];
    }

    public function testChange() {
        $this->specify('successfully change username to new one', function() {
            $model = $this->createModel([
                'password' => 'password_0',
                'username' => 'my_new_nickname',
            ]);
            expect($model->change())->true();
            expect(Account::findOne($this->getAccountId())->username)->equals('my_new_nickname');
            expect(UsernameHistory::findOne(['username' => 'my_new_nickname']))->isInstanceOf(UsernameHistory::class);
        });
    }

    public function testChangeWithoutChange() {
        $this->specify('no new UsernameHistory record, if we don\'t change nickname', function() {
            $model = $this->createModel([
                'password' => 'password_0',
                'username' => $this->accounts['admin']['username'],
            ]);
            $callTime = time();
            expect($model->change())->true();
            expect(UsernameHistory::findOne([
                'AND',
                'username' => $this->accounts['admin']['username'],
                ['>=', 'applied_in', $callTime],
            ]))->null();
        });
    }

    public function testChangeCase() {
        $this->specify('username should change, if we change case of some letters', function() {
            $newUsername = mb_strtoupper($this->accounts['admin']['username']);
            $model = $this->createModel([
                'password' => 'password_0',
                'username' => $newUsername,
            ]);
            expect($model->change())->true();
            expect(Account::findOne($this->getAccountId())->username)->equals($newUsername);
            expect(UsernameHistory::findOne(['username' => $newUsername]))->isInstanceOf(UsernameHistory::class);
        });
    }

    public function testValidateUsername() {
        $this->specify('error.username_not_available expected if username is already taken', function() {
            $model = $this->createModel([
                'password' => 'password_0',
                'username' => 'Jon',
            ]);
            $model->validateUsername('username');
            expect($model->getErrors('username'))->equals(['error.username_not_available']);
        });

        $this->specify('error.username_not_available is NOT expected if username is already taken by CURRENT user', function() {
            $model = $this->createModel([
                'password' => 'password_0',
                'username' => $this->accounts['admin']['username'],
            ]);
            $model->validateUsername('username');
            expect($model->getErrors('username'))->isEmpty();
        });
    }

    public function testCreateTask() {
        $model = $this->createModel();
        $model->createEventTask('1', 'test1', 'test');
        // TODO: у меня пока нет идей о том, чтобы это как-то успешно протестировать, увы
        // но по крайней мере можно убедиться, что оно не падает где-то на этом шаге
    }

    private function createModel(array $params = []) : ChangeUsernameForm {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $params = array_merge($params, [
            'accountId' => $this->getAccountId(),
        ]);

        return new class($params) extends ChangeUsernameForm {
            public $accountId;

            protected function getAccount() {
                return Account::findOne($this->accountId);
            }
        };
    }

    private function getAccountId() {
        return $this->accounts['admin']['id'];
    }

}
