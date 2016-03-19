<?php
namespace tests\codeception\api\models;

use api\models\ChangeUsernameForm;
use Codeception\Specify;
use common\models\Account;
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
        });
    }

}

// TODO: тут образуется магическая переменная 1, что не круто. После перехода на php7 можно заюзать анонимный класс
// и создавать модель прямо внутри теста, где доступен объект фикстур с именами переменных

class DummyChangeUsernameForm extends ChangeUsernameForm {

    protected function getAccount() {
        return Account::findOne(1);
    }

}
