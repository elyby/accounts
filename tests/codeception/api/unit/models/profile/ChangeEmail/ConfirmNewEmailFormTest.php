<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\ConfirmNewEmailForm;
use Codeception\Specify;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property AccountFixture $accounts
 * @property EmailActivationFixture $emailActivations
 */
class ConfirmNewEmailFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testChangeEmail() {
        $this->specify('successfully change account email', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['account-with-change-email-finish-state']['id']);
            $model = new ConfirmNewEmailForm($account, [
                'key' => $this->emailActivations['newEmailConfirmation']['key'],
            ]);
            expect($model->changeEmail())->true();
            expect(EmailActivation::findOne([
                'account_id' => $account->id,
                'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
            ]))->null();
            $data = unserialize($this->emailActivations['newEmailConfirmation']['_data']);
            expect($account->email)->equals($data['newEmail']);
        });
    }

}
