<?php
namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\base\ApiForm;
use common\components\UserFriendlyRandomKey;
use common\helpers\Error as E;
use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\models\EmailActivation;
use common\tasks\SendRegistrationEmail;
use Webmozart\Assert\Assert;
use Yii;

class RepeatAccountActivationForm extends ApiForm {

    public $captcha;

    public $email;

    private $emailActivation;

    public function rules() {
        return [
            ['captcha', ReCaptchaValidator::class],
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required', 'message' => E::EMAIL_REQUIRED],
            ['email', 'validateEmailForAccount'],
            ['email', 'validateExistsActivation'],
        ];
    }

    public function validateEmailForAccount($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account === null) {
                $this->addError($attribute, E::EMAIL_NOT_FOUND);
            } elseif ($account->status === Account::STATUS_ACTIVE) {
                $this->addError($attribute, E::ACCOUNT_ALREADY_ACTIVATED);
            } elseif ($account->status !== Account::STATUS_REGISTERED) {
                // TODO: такие аккаунты следует логировать за попытку к саботажу
                $this->addError($attribute, E::ACCOUNT_CANNOT_RESEND_MESSAGE);
            }
        }
    }

    public function validateExistsActivation($attribute) {
        if (!$this->hasErrors()) {
            $activation = $this->getActivation();
            if ($activation !== null && !$activation->canRepeat()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    /**
     * @CollectModelMetrics(prefix="signup.repeatEmail")
     * @return bool
     */
    public function sendRepeatMessage() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $transaction = Yii::$app->db->beginTransaction();

        EmailActivation::deleteAll([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        ]);

        $activation = new RegistrationConfirmation();
        $activation->account_id = $account->id;
        $activation->key = UserFriendlyRandomKey::make();
        Assert::true($activation->save(), 'Unable save email-activation model.');

        $this->emailActivation = $activation;

        Yii::$app->queue->push(SendRegistrationEmail::createFromConfirmation($activation));

        $transaction->commit();

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
    public function getActivation() {
        if ($this->emailActivation === null) {
            $this->emailActivation = $this->getAccount()
                ->getEmailActivations()
                ->andWhere(['type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION])
                ->one();
        }

        return $this->emailActivation;
    }

}
