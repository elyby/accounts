<?php
namespace tests\codeception\api\models\authentication;

use api\components\User\LoginResult;
use api\models\authentication\ConfirmEmailForm;
use Codeception\Specify;
use common\models\Account;
use common\models\AccountSession;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property EmailActivationFixture $emailActivations
 */
class ConfirmEmailFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    protected function createModel($key) {
        return new ConfirmEmailForm([
            'key' => $key,
        ]);
    }

    public function testConfirm() {
        $fixture = $this->emailActivations['freshRegistrationConfirmation'];
        $model = $this->createModel($fixture['key']);
        $this->specify('expect true result', function() use ($model, $fixture) {
            $result = $model->confirm();
            expect($result)->isInstanceOf(LoginResult::class);
            expect('session was generated', $result->getSession())->isInstanceOf(AccountSession::class);
            $activationExists = EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists();
            expect('email activation key is not exist', $activationExists)->false();
            /** @var Account $user */
            $user = Account::findOne($fixture['account_id']);
            expect('user status changed to active', $user->status)->equals(Account::STATUS_ACTIVE);
        });
    }

}
