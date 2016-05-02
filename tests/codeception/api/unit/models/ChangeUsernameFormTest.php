<?php
namespace tests\codeception\api\models;

use api\models\ChangeUsernameForm;
use Codeception\Specify;
use common\models\Account;
use common\models\UsernameHistory;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

/**
 * @property array $accounts
 */
class ChangeUsernameFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }

    public function testChange() {
        $this->specify('successfully change username to new one', function() {
            $model = new DummyChangeUsernameForm([
                'password' => 'password_0',
                'username' => 'my_new_nickname',
            ]);
            expect($model->change())->true();
            expect(Account::findOne(1)->username)->equals('my_new_nickname');
            expect(UsernameHistory::findOne(['username' => 'my_new_nickname']))->isInstanceOf(UsernameHistory::class);
        });
    }

    public function testUsernameUnavailable() {
        $this->specify('error.username_not_available expected if username is already taken', function() {
            $model = new DummyChangeUsernameForm([
                'password' => 'password_0',
                'username' => 'Jon',
            ]);
            $model->validate();
            expect($model->getErrors('username'))->equals(['error.username_not_available']);
        });

        $this->specify('error.username_not_available is NOT expected if username is already taken by CURRENT user', function() {
            $model = new DummyChangeUsernameForm([
                'password' => 'password_0',
                'username' => 'Admin',
            ]);
            $model->validate();
            expect($model->getErrors('username'))->equals([]);
        });
    }

    public function testCreateTask() {
        $model = new DummyChangeUsernameForm();
        $model->createTask('1', 'test1', 'test');
        // TODO: у меня пока нет идей о том, чтобы это как-то успешно протестировать, увы
        // но по крайней мере можно убедиться, что оно не падает где-то на этом шаге
    }

}

// TODO: тут образуется магическая переменная 1, что не круто. После перехода на php7 можно заюзать анонимный класс
// и создавать модель прямо внутри теста, где доступен объект фикстур с именами переменных

class DummyChangeUsernameForm extends ChangeUsernameForm {

    protected function getAccount() {
        return Account::findOne(1);
    }

}
