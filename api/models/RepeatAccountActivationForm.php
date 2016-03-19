<?php
namespace api\models;

use api\models\base\ApiForm;
use common\components\UserFriendlyRandomKey;
use common\models\Account;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;

class RepeatAccountActivationForm extends ApiForm {

    // Частота повтора отправки нового письма
    const REPEAT_FREQUENCY = 5 * 60;

    public $email;

    public function rules() {
        return [
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required', 'message' => 'error.email_required'],
            ['email', 'validateEmailForAccount'],
            ['email', 'validateExistsActivation'],
        ];
    }

    public function validateEmailForAccount($attribute) {
        if (!$this->hasErrors($attribute)) {
            $account = $this->getAccount();
            if ($account === null) {
                $this->addError($attribute, "error.{$attribute}_not_found");
            } elseif ($account->status === Account::STATUS_ACTIVE) {
                $this->addError($attribute, "error.account_already_activated");
            } elseif ($account->status !== Account::STATUS_REGISTERED) {
                // TODO: такие аккаунты следует логировать за попытку к саботажу
                $this->addError($attribute, "error.account_cannot_resend_message");
            }
        }
    }

    public function validateExistsActivation($attribute) {
        if (!$this->hasErrors($attribute)) {
            if ($this->getActiveActivation() !== null) {
                $this->addError($attribute, 'error.recently_sent_message');
            }
        }
    }

    public function sendRepeatMessage() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            EmailActivation::deleteAll([
                'account_id' => $account->id,
                'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
            ]);

            $activation = new EmailActivation();
            $activation->account_id = $account->id;
            $activation->type = EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION;
            $activation->key = UserFriendlyRandomKey::make();
            if (!$activation->save()) {
                throw new ErrorException('Unable save email-activation model.');
            }

            $regForm = new RegistrationForm();
            $regForm->sendMail($activation, $account);

            $transaction->commit();
        } catch (ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * @return Account|null
     */
    public function getAccount() {
        return Account::find()
               ->andWhere(['email' => $this->email])
               ->one();
    }

    /**
     * @return EmailActivation|null
     */
    public function getActiveActivation() {
        return $this->getAccount()
            ->getEmailActivations()
            ->andWhere(['type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION])
            ->andWhere(['>=', 'created_at', time() - self::REPEAT_FREQUENCY])
            ->one();
    }

}
