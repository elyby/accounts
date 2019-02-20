<?php
namespace api\tests\_support\models\authentication;

use api\components\User\AuthenticationResult;
use api\models\authentication\ConfirmEmailForm;
use common\models\Account;
use common\models\AccountSession;
use common\models\EmailActivation;
use api\tests\unit\TestCase;
use common\tests\fixtures\EmailActivationFixture;

class ConfirmEmailFormTest extends TestCase {

    public function _fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testConfirm() {
        $fixture = $this->tester->grabFixture('emailActivations', 'freshRegistrationConfirmation');
        $model = $this->createModel($fixture['key']);
        $result = $model->confirm();
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertInstanceOf(AccountSession::class, $result->getSession(), 'session was generated');
        $activationExists = EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists();
        $this->assertFalse($activationExists, 'email activation key is not exist');
        /** @var Account $account */
        $account = Account::findOne($fixture['account_id']);
        $this->assertEquals(Account::STATUS_ACTIVE, $account->status, 'user status changed to active');
    }

    private function createModel($key) {
        return new ConfirmEmailForm([
            'key' => $key,
        ]);
    }

}
