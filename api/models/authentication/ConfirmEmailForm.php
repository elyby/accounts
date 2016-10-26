<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\KeyConfirmationForm;
use api\models\profile\ChangeUsernameForm;
use common\models\Account;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;

class ConfirmEmailForm extends KeyConfirmationForm {

    public function confirm() {
        if (!$this->validate()) {
            return false;
        }

        $confirmModel = $this->getActivationCodeModel();
        if ($confirmModel->type != EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION) {
            $confirmModel->delete();
            // TODO: вот где-то здесь нужно ещё попутно сгенерировать соответствующую ошибку
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
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
        } catch (ErrorException $e) {
            $transaction->rollBack();
            if (YII_DEBUG) {
                throw $e;
            } else {
                return false;
            }
        }

        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;

        return $component->login(new AccountIdentity($account->attributes), true);
    }

}
