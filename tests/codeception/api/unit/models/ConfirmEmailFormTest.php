<?php
namespace tests\codeception\api\models;

use api\models\ConfirmEmailForm;
use Codeception\Specify;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property array $emailActivations
 */
class ConfirmEmailFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    protected function createModel($key) {
        return new ConfirmEmailForm([
            'key' => $key,
        ]);
    }

    public function testValidInput() {
        $fixture = $this->emailActivations[0];
        $model = $this->createModel($fixture['key']);
        $this->specify('expect true result', function() use ($model, $fixture) {
            expect('model return successful result', $model->confirm())->notEquals(false);
            expect('email activation key is not exist', EmailActivation::find()->andWhere(['key' => $fixture['key']])->exists())->false();
            /** @var Account $user */
            $user = Account::findOne($fixture['account_id']);
            expect('user status changed to active', $user->status)->equals(Account::STATUS_ACTIVE);
        });
    }

}
