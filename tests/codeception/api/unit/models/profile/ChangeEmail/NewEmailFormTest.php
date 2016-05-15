<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\NewEmailForm;
use Codeception\Specify;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property AccountFixture $accounts
 * @property EmailActivationFixture $emailActivations
 */
class NewEmailFormTest extends DbTestCase {
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
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testCreateCode() {
        $this->specify('create valid code and store it to database', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);
            $model = new NewEmailForm($account);
            $model->email = 'my-new-email@ely.by';
            $activationModel = $model->createCode();
            expect($activationModel)->isInstanceOf(NewEmailConfirmation::class);
            expect($activationModel->account_id)->equals($account->id);
            expect($activationModel->newEmail)->equals($model->email);
            expect(EmailActivation::findOne($activationModel->key))->notNull();
        });
    }

    public function testSendNewEmailConfirmation() {
        $this->specify('send email', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);
            /** @var NewEmailForm $model */
            $model = new NewEmailForm($account, [
                'key' => $this->emailActivations['currentEmailConfirmation']['key'],
                'email' => 'my-new-email@ely.by',
            ]);
            expect($model->sendNewEmailConfirmation())->true();
            expect(EmailActivation::findOne($this->emailActivations['currentEmailConfirmation']['key']))->null();
            expect(EmailActivation::findOne([
                'account_id' => $account->id,
                'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
            ]))->notNull();
            expect_file($this->getMessageFile())->exists();
        });
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        return Yii::getAlias($mailer->fileTransportPath) . '/testing_message.eml';
    }

}
