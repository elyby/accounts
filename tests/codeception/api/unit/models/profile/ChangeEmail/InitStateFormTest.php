<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\InitStateForm;
use Codeception\Specify;
use common\models\Account;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property AccountFixture $accounts
 * @property EmailActivationFixture $emailActivations
 */
class InitStateFormTest extends DbTestCase {
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

    public function testValidateAccountPasswordHashStrategy() {
        $this->specify('we cannot change password on old password hash strategy', function() {
            $account = new Account();
            $account->password_hash_strategy = Account::PASS_HASH_STRATEGY_OLD_ELY;
            $model = new InitStateForm($account);
            $model->validateAccountPasswordHashStrategy('email');
            expect($model->getErrors('email'))->equals(['error.old_hash_strategy']);
        });

        $this->specify('no errors on modern password hash strategy', function() {
            $account = new Account();
            $account->password_hash_strategy = Account::PASS_HASH_STRATEGY_YII2;
            $model = new InitStateForm($account);
            $model->validateAccountPasswordHashStrategy('email');
            expect($model->getErrors('email'))->isEmpty();
        });
    }

    public function testCreateCode() {
        $this->specify('create valid code and store it to database', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);
            $model = new InitStateForm($account);
            $activationModel = $model->createCode();
            expect($activationModel)->isInstanceOf(CurrentEmailConfirmation::class);
            expect($activationModel->account_id)->equals($account->id);
            expect(EmailActivation::findOne($activationModel->key))->notNull();
        });
    }

    public function testSendCurrentEmailConfirmation() {
        $this->specify('send email', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);
            $model = new InitStateForm($account);
            expect($model->sendCurrentEmailConfirmation())->true();
            expect(EmailActivation::find()->andWhere([
                'account_id' => $account->id,
                'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
            ])->exists())->true();
            expect_file($this->getMessageFile())->exists();
        });
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        return Yii::getAlias($mailer->fileTransportPath) . '/testing_message.eml';
    }

}
