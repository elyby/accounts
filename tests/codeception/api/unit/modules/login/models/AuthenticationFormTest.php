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

    protected function createModel($email = '', $password = '') {
        return new AuthenticationForm([
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function testLoginEmail() {
        $model = $this->createModel();
        $this->specify('error.email_required expected if email is not set', function() use ($model) {
            expect($model->login())->false();
            expect($model->getErrors('email'))->equals(['error.email_required']);
            expect(Yii::$app->user->isGuest)->true();
        });

        $model = $this->createModel('wrong-email-string');
        $this->specify('error.email_invalid expected if email not correct', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('email'))->equals(['error.email_invalid']);
        });

        $model = $this->createModel('wrong@email');
        $this->specify('error.email_invalid expected if email not correct', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('email'))->equals(['error.email_invalid']);
        });

        $model = $this->createModel('not-exist@user.com');
        $this->specify('error.email_not_exist expected if email not exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('email'))->equals(['error.email_not_exist']);
        });

        $model = $this->createModel('admin@ely.by');
        $this->specify('no errors on email field if email is correct and exists in database', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('email'))->isEmpty();
        });
    }

    public function testLoginPassword() {
        $model = $this->createModel();
        $this->specify('password don\'t has errors if email not set', function() use ($model) {
            expect($model->login())->false();
            expect(Yii::$app->user->isGuest)->true();
            expect($model->getErrors('password'))->isEmpty();
        });

        $model = $this->createModel('wrong-email-string', 'random-password');
        $this->specify('password don\'t has errors if email invalid', function() use ($model) {
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
    }

    public function testLoginCorrect() {
        $model = $this->createModel('admin@ely.by', 'password_0');
        $this->specify('user should be able to login with correct credentials', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->isEmpty();
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }

}
