<?php
namespace api\models\authentication;

use api\models\base\ApiForm;
use api\modules\accounts\models\ChangeUsernameForm;
use api\validators\EmailActivationKeyValidator;
use common\models\Account;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;

class ConfirmEmailForm extends ApiForm {

    public $key;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION],
        ];
    }

    /**
     * @return \api\components\User\AuthenticationResult|bool
     * @throws ErrorException
     */
    public function confirm() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\RegistrationConfirmation $confirmModel */
        $confirmModel = $this->key;
        $account = $confirmModel->account;
        $account->status = Account::STATUS_ACTIVE;
        if (!$confirmModel->delete()) {
            throw new ErrorException('Unable remove activation key.');
        }

        if (!$account->save()) {
            throw new ErrorException('Unable activate user account.');
        }

        $changeUsernameForm = new ChangeUsernameForm($account);
        $changeUsernameForm->createEventTask($account->id, $account->username, null);

        $transaction->commit();

        return Yii::$app->user->createJwtAuthenticationToken($account, true);
    }

}
