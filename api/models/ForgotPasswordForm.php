<?php
namespace api\models;

use api\models\base\ApiForm;
use api\traits\AccountFinder;
use common\components\UserFriendlyRandomKey;
use common\models\Account;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;

class ForgotPasswordForm extends ApiForm {
    use AccountFinder;

    public $login;

    public function rules() {
        return [
            ['login', 'required', 'message' => 'error.login_required'],
            ['login', 'validateLogin'],
            ['login', 'validateActivity'],
            ['login', 'validateFrequency'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, 'error.' . $attribute . '_not_exist');
            }
        }
    }

    public function validateActivity($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status !== Account::STATUS_ACTIVE) {
                $this->addError($attribute, 'error.account_not_activated');
            }
        }
    }

    public function validateFrequency($attribute) {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canRepeat()) {
                $this->addError($attribute, 'error.email_frequency');
            }
        }
    }

    public function forgotPassword() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $emailActivation = $this->getEmailActivation();
        if ($emailActivation === null) {
            $emailActivation = new ForgotPassword();
            $emailActivation->account_id = $account->id;
        } else {
            $emailActivation->created_at = time();
        }

        $emailActivation->key = UserFriendlyRandomKey::make();
        if (!$emailActivation->save()) {
            throw new ErrorException('Cannot create email activation for forgot password form');
        }

        $this->sendMail($emailActivation);

        return true;
    }

    public function sendMail(EmailActivation $emailActivation) {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $fromEmail = Yii::$app->params['fromEmail'];
        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        $acceptor = $emailActivation->account;
        /** @var \yii\swiftmailer\Message $message */
        $message = $mailer->compose([
            'html' => '@app/mails/forgot-password-html',
            'text' => '@app/mails/forgot-password-text',
        ], [
            'key' => $emailActivation->key,
        ])
            ->setTo([$acceptor->email => $acceptor->username])
            ->setFrom([$fromEmail => 'Ely.by Accounts'])
            ->setSubject('Ely.by Account forgot password');

        if (!$message->send()) {
            throw new ErrorException('Unable send email with activation code.');
        }
    }

    public function getLogin() {
        return $this->login;
    }

    /**
     * @return EmailActivation|null
     * @throws ErrorException
     */
    public function getEmailActivation() {
        $account = $this->getAccount();
        if ($account === null) {
            throw new ErrorException('Account not founded');
        }

        return $account->getEmailActivations()
            ->andWhere(['type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY])
            ->one();
    }

}
