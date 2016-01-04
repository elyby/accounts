<?php
namespace tests\codeception\api\modules\login\models;

use api\modules\login\models\AuthenticationForm;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

class AuthenticationFormTest extends DbTestCase {

    use Specify;

    protected function tearDown() {
        Yii::$app->user->logout();
        parent::tearDown();
    }

    public function fixtures() {
        return [
            'account' => [
                'class' => AccountFixture::className(),
                'dataFile' => '@tests/codeception/api/unit/fixtures/data/models/accounts.php'
            ],
        ];
    }

    protected function createModel($login = '', $password = '') {
        return new AuthenticationForm([
            'login' => $login,
            'password' => $password,
        ]);
    }

    public function testLoginEmailOrUsername() {
        $model = $this->createModel();
        $this->specify('error.login_required expected if login is not set', function() use ($model) {
            expect($model->login())->false();
            expect($model->getErrors('login'))->equals(['error.login_required']);
            expect(Yii::$app->user->isGuest)->true();
        });

        $model = $this->createModel('non-exist-username');
        $this->specify('error.login_not_exist expected if username not exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $model = $this->createModel('not-exist@user.com');
        $this->specify('error.login_not_exist expected if email not exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $model = $this->createModel('Admin');
        $this->specify('no errors on login field if username is correct and exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('login'))->isEmpty();
        });

        $model = $this->createModel('admin@ely.by');
        $this->specify('no errors on login field if email is correct and exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testLoginPassword() {
        $model = $this->createModel();
        $this->specify('password don\'t has errors if email or username not set', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->isEmpty();
        });

        $model = $this->createModel('non-exist-username', 'random-password');
        $this->specify('password don\'t has errors if username not exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->isEmpty();
        });

        $model = $this->createModel('not-exist@user.com', 'random-password');
        $this->specify('password don\'t has errors if email not exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->isEmpty();
        });

        $model = $this->createModel('admin@ely.by', 'wrong-password');
        $this->specify('error.password_incorrect expected if email correct, but password wrong', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->equals(['error.password_incorrect']);
        });

        $model = $this->createModel('Admin', 'wrong-password');
        $this->specify('error.password_incorrect expected if username correct, but password wrong', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->equals(['error.password_incorrect']);
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
