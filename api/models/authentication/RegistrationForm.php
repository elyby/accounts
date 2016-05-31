<?php
namespace api\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\base\ApiForm;
use api\models\profile\ChangeUsernameForm;
use common\components\UserFriendlyRandomKey;
use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\models\EmailActivation;
use common\validators\LanguageValidator;
use common\validators\PasswordValidate;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;

class RegistrationForm extends ApiForm {

    public $username;
    public $email;
    public $password;
    public $rePassword;
    public $rulesAgreement;
    public $lang;

    public function rules() {
        return [
            [[], ReCaptchaValidator::class, 'message' => 'error.captcha_invalid', 'when' => !YII_ENV_TEST],
            ['rulesAgreement', 'required', 'message' => 'error.you_must_accept_rules'],

            ['username', 'validateUsername', 'skipOnEmpty' => false],
            ['email', 'validateEmail', 'skipOnEmpty' => false],

            ['password', 'required', 'message' => 'error.password_required'],
            ['rePassword', 'required', 'message' => 'error.rePassword_required'],
            ['password', PasswordValidate::class],
            ['rePassword', 'validatePasswordAndRePasswordMatch'],

            ['lang', LanguageValidator::class],
        ];
    }

    public function validateUsername() {
        $account = new Account();
        $account->username = $this->username;
        if (!$account->validate(['username'])) {
            $this->addErrors($account->getErrors());
        }
    }

    public function validateEmail() {
        $account = new Account();
        $account->email = $this->email;
        if (!$account->validate(['email'])) {
            $this->addErrors($account->getErrors());
        }
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->password !== $this->rePassword) {
                $this->addError($attribute, "error.rePassword_does_not_match");
            }
        }
    }

    /**
     * @return Account|null the saved model or null if saving fails
     */
    public function signup() {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $account = new Account();
            $account->uuid = Uuid::uuid4();
            $account->email = $this->email;
            $account->username = $this->username;
            $account->password = $this->password;
            $account->lang = $this->lang;
            $account->status = Account::STATUS_REGISTERED;
            if (!$account->save()) {
                throw new ErrorException('Account not created.');
            }

            $emailActivation = new RegistrationConfirmation();
            $emailActivation->account_id = $account->id;
            $emailActivation->key = UserFriendlyRandomKey::make();

            if (!$emailActivation->save()) {
                throw new ErrorException('Unable save email-activation model.');
            }

            $this->sendMail($emailActivation, $account);

            $transaction->commit();
        } catch (ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        }

        $changeUsernameForm = new ChangeUsernameForm();
        $changeUsernameForm->createTask($account->id, $account->username, null);

        return $account;
    }

    // TODO: подумать, чтобы вынести этот метод в какую-то отдельную конструкцию, т.к. используется и внутри NewAccountActivationForm
    public function sendMail(EmailActivation $emailActivation, Account $account) {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $fromEmail = Yii::$app->params['fromEmail'];

        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        /** @var \yii\swiftmailer\Message $message */
        $message = $mailer->compose([
            'html' => '@app/mails/registration-confirmation-html',
            'text' => '@app/mails/registration-confirmation-text',
        ], [
                'key' => $emailActivation->key,
            ])
            ->setTo([$account->email => $account->username])
            ->setFrom([$fromEmail => 'Ely.by Accounts'])
            ->setSubject('Ely.by Account registration');

        if (!$message->send()) {
            throw new ErrorException('Unable send email with activation code.');
        }
    }

}