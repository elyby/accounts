<?php
namespace tests\codeception\api\models\authentication;

use api\models\authentication\RepeatAccountActivationForm;
use Codeception\Specify;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property array $accounts
 * @property array $activations
 */
class RepeatAccountActivationFormTest extends DbTestCase {
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
            'activations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    public function testValidateEmailForAccount() {
        $this->specify('error.email_not_found if passed valid email, but it don\'t exists in database', function() {
            $model = new RepeatAccountActivationForm(['email' => 'me-is-not@exists.net']);
            $model->validateEmailForAccount('email');
            expect($model->getErrors('email'))->equals(['error.email_not_found']);
        });

        $this->specify('error.account_already_activated if passed valid email, but account already activated', function() {
            $model = new RepeatAccountActivationForm(['email' => $this->accounts['admin']['email']]);
            $model->validateEmailForAccount('email');
            expect($model->getErrors('email'))->equals(['error.account_already_activated']);
        });

        $this->specify('no errors if passed valid email for not activated account', function() {
            $model = new RepeatAccountActivationForm(['email' => $this->accounts['not-activated-account']['email']]);
            $model->validateEmailForAccount('email');
            expect($model->getErrors('email'))->isEmpty();
        });
    }

    public function testValidateExistsActivation() {
        $this->specify('error.recently_sent_message if passed email has recently sent message', function() {
            $model = $this->createModel([
                'emailKey' => $this->activations['freshRegistrationConfirmation']['key'],
            ]);
            $model->validateExistsActivation('email');
            expect($model->getErrors('email'))->equals(['error.recently_sent_message']);
        });

        $this->specify('no errors if passed email has expired activation message', function() {
            $model = $this->createModel([
                'emailKey' => $this->activations['oldRegistrationConfirmation']['key'],
            ]);
            $model->validateExistsActivation('email');
            expect($model->getErrors('email'))->isEmpty();
        });
    }

    public function testSendRepeatMessage() {
        $this->specify('no magic if we don\'t pass validation', function() {
            $model = new RepeatAccountActivationForm();
            expect($model->sendRepeatMessage())->false();
            expect_file($this->getMessageFile())->notExists();
        });

        $this->specify('successfully send new message if previous message has expired', function() {
            $email = $this->accounts['not-activated-account-with-expired-message']['email'];
            $model = new RepeatAccountActivationForm(['email' => $email]);
            expect($model->sendRepeatMessage())->true();
            expect($model->getActivation())->notNull();
            expect_file($this->getMessageFile())->exists();
        });
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        return Yii::getAlias($mailer->fileTransportPath) . '/testing_message.eml';
    }

    /**
     * @param array $params
     * @return RepeatAccountActivationForm
     */
    private function createModel(array $params = []) {
        return new class($params) extends RepeatAccountActivationForm {
            public $emailKey;

            public function getActivation() {
                return EmailActivation::findOne($this->emailKey);
            }
        };
    }

}
