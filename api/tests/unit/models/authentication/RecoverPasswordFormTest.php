<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\models\authentication\RecoverPasswordForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\EmailActivation;
use common\tests\fixtures\EmailActivationFixture;

class RecoverPasswordFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testRecoverPassword(): void {
        $fixture = $this->tester->grabFixture('emailActivations', 'freshPasswordRecovery');
        $model = new RecoverPasswordForm([
            'key' => $fixture['key'],
            'newPassword' => '12345678',
            'newRePassword' => '12345678',
        ]);
        $result = $model->recoverPassword();
        $this->assertNotNull($result);
        $this->assertNull($result->getRefreshToken(), 'session was not generated');
        $this->assertFalse(EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists());
        /** @var Account $account */
        $account = Account::findOne($fixture['account_id']);
        $this->assertTrue($account->validatePassword('12345678'));
    }

}
