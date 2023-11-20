<?php
namespace api\modules\accounts\models;

use api\validators\EmailActivationKeyValidator;
use common\models\EmailActivation;
use Webmozart\Assert\Assert;
use Yii;

class ChangeEmailForm extends AccountActionForm {

    public $key;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION],
        ];
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\NewEmailConfirmation $activation */
        $activation = $this->key;
        $activation->delete();

        $account = $this->getAccount();
        $account->email = $activation->newEmail;
        Assert::true($account->save(), 'Cannot save new account email value');

        $transaction->commit();

        return true;
    }

}
