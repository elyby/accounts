<?php
declare(strict_types=1);

namespace api\tests\_support\models\authentication;

use api\models\authentication\ConfirmEmailForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\EmailActivation;
use common\tests\fixtures\EmailActivationFixture;

class ConfirmEmailFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testConfirm() {
        $fixture = $this->tester->grabFixture('emailActivations', 'freshRegistrationConfirmation');
        $model = $this->createModel($fixture['key']);
        $result = $model->confirm();
        $this->assertNotNull($result);
        $this->assertNotNull($result->getRefreshToken(), 'session was generated');
        $activationExists = EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists();
        $this->assertFalse($activationExists, 'email activation key is not exist');
        /** @var Account $account */
        $account = Account::findOne($fixture['account_id']);
        $this->assertSame(Account::STATUS_ACTIVE, $account->status, 'user status changed to active');
    }

    private function createModel($key) {
        return new ConfirmEmailForm([
            'key' => $key,
        ]);
    }

}
