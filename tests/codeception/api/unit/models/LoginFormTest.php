<?php
namespace tests\codeception\api\models;

use api\models\LoginForm;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

class LoginFormTest extends DbTestCase {
    use Specify;

    protected function tearDown() {
        Yii::$app->user->logout();
        parent::tearDown();
    }

    public function fixtures() {
        return [
            'account' => [
                'class' => AccountFixture::className(),
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
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginByUsernameCorrect() {
        $model = $this->createModel('Admin', 'password_0');
        $this->specify('user should be able to login with correct username and password', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->isEmpty();
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }

    public function testLoginByEmailCorrect() {
        $model = $this->createModel('admin@ely.by', 'password_0');
        $this->specify('user should be able to login with correct email and password', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->isEmpty();
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }

}
