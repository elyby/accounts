<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\InitStateForm;
use common\models\Account;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;

class InitStateFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testCreateCode() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new InitStateForm($account);
        $activationModel = $model->createCode();
        $this->assertInstanceOf(CurrentEmailConfirmation::class, $activationModel);
        $this->assertEquals($account->id, $activationModel->account_id);
        $this->assertNotNull(EmailActivation::findOne($activationModel->key));
    }

    public function testSendCurrentEmailConfirmation() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new InitStateForm($account, [
            'password' => 'password_0',
        ]);
        $this->assertTrue($model->sendCurrentEmailConfirmation());
        $this->assertTrue(EmailActivation::find()->andWhere([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
        ])->exists());
        $this->tester->canSeeEmailIsSent();
    }

}
