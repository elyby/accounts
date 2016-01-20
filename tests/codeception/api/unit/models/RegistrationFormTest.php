<?php
namespace tests\codeception\api\models;

use api\models\RegistrationForm;
use Codeception\Specify;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

/**
 * @property array $accounts
 */
class RegistrationFormTest extends DbTestCase {
    use Specify;

    public function setUp() {
        parent::setUp();
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->fileTransportCallback = function () {
            return 'testing_message.eml';
        };
    }

    protected function tearDown() {
        if (file_exists($this->getMessageFile())) {
            unlink($this->getMessageFile());
        }

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

    public function testNotCorrectRegistration() {
        $model = new RegistrationForm([
            'username' => 'valid_nickname',
            'email' => 'correct-email@ely.by',
            'password' => 'enough-length',
            'rePassword' => 'password',
            'rulesAgreement' => true,
        ]);
        $this->specify('username and email in use, passwords not math - model is not created', function() use ($model) {
            expect($model->signup())->null();
            expect($model->getErrors())->notEmpty();
            expect_file($this->getMessageFile())->notExists();
        });
    }

    public function testUsernameValidators() {
        $shouldBeValid = [
            'русский_ник', 'русский_ник_на_грани!', 'numbers1132', '*__*-Stars-*__*', '1-_.!?#$%^&*()[]', '[ESP]Эрик',
            'Свят_помидор;', 'зроблена_ў_беларусі:)',
        ];
        $shouldBeInvalid = [
            'nick@name', 'spaced nick', '   ', 'sh', '  sh  ',
        ];

        foreach($shouldBeValid as $nickname) {
            $model = new RegistrationForm([
                'username' => $nickname,
            ]);
            expect($nickname . ' passed validation', $model->validate(['username']))->true();
        }

        foreach($shouldBeInvalid as $nickname) {
            $model = new RegistrationForm([
                'username' => $nickname,
            ]);
            expect($nickname . ' fail validation', $model->validate('username'))->false();
        }
    }

    public function testCorrectSignup() {
        $model = new RegistrationForm([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
            'password' => 'some_password',
            'rePassword' => 'some_password',
            'rulesAgreement' => true,
        ]);

        $user = $model->signup();

        expect('user should be valid', $user)->isInstanceOf(Account::class);
        expect('password should be correct', $user->validatePassword('some_password'))->true();
        expect('user model exists in database', Account::find()->andWhere([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
        ])->exists())->true();
        expect('email activation code exists in database', EmailActivation::find()->andWhere([
            'account_id' => $user->id,
            'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        ])->exists())->true();
        expect_file('message file exists', $this->getMessageFile())->exists();
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        return Yii::getAlias($mailer->fileTransportPath) . '/testing_message.eml';
    }

}
