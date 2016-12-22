<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\ApiForm;
use api\models\profile\ChangeUsernameForm;
use api\validators\EmailActivationKeyValidator;
use common\models\Account;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;

class ConfirmEmailForm extends ApiForm {

    public $key;

    public function rules() {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION],
        ];
    }

    /**
     * @return \api\components\User\LoginResult|bool
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

        $changeUsernameForm = new ChangeUsernameForm();
        $changeUsernameForm->createEventTask($account->id, $account->username, null);

        $transaction->commit();

        return Yii::$app->user->login(new AccountIdentity($account->attributes), true);
    }

}
