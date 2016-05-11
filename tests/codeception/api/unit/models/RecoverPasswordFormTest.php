<?php
namespace tests\codeception\api\models;

use api\models\RecoverPasswordForm;
use Codeception\Specify;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property array $emailActivations
 */
class RecoverPasswordFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    public function testRecoverPassword() {
        $fixture = $this->emailActivations['freshPasswordRecovery'];
        $this->specify('change user account password by email confirmation key', function() use ($fixture) {
            $model = new RecoverPasswordForm([
                'key' => $fixture['key'],
                'newPassword' => '12345678',
                'newRePassword' => '12345678',
            ]);
            expect($model->recoverPassword())->notEquals(false);
            $activationExists = EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists();
            expect($activationExists)->false();
            /** @var Account $account */
            $account = Account::findOne($fixture['account_id']);
            expect($account->validatePassword('12345678'))->true();
        });
    }

}