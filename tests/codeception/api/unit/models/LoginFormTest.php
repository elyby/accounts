<?php
namespace tests\codeception\api\models;

use api\models\LoginForm;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

/**
 * @property array $accounts
 */
class LoginFormTest extends DbTestCase {
    use Specify;

    protected function tearDown() {
        Yii::$app->user->logout();
        parent::tearDown();
    }

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }

    protected function createModel($login = '', $password = '') {
        return new LoginForm([
            'login' => $login,
            'password' => $password,
        ]);
    }

    public function testIncorrectLogin() {
        $model = $this->createModel('not-esist-login', 'fully-invalid-password');
        $this->specify('get errors and don\'t log in into account with wrong credentials', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('error messages should be set', $model->errors)->notEmpty();
        });

        $model = $this->createModel($this->accounts['not-activated-account']['username'], 'password_0');
        $this->specify('get error if account data valid, but account is not activated', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('error messages should be set', $model->errors)->equals([
                'login' => [
                    'error.account_not_activated',
                ],
            ]);
        });
    }

    public function testLoginByUsernameCorrect() {
        $model = $this->createModel($this->accounts['admin']['username'], 'password_0');
        $this->specify('user should be able to login with correct username and password', function () use ($model) {
            expect('model should login user', $model->login())->notEquals(false);
            expect('error message should not be set', $model->errors)->isEmpty();
        });
    }

    public function testLoginByEmailCorrect() {
        $model = $this->createModel($this->accounts['admin']['email'], 'password_0');
        $this->specify('user should be able to login with correct email and password', function () use ($model) {
            expect('model should login user', $model->login())->notEquals(false);
            expect('error message should not be set', $model->errors)->isEmpty();
        });
    }

}
