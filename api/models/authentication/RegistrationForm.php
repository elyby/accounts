<?php
namespace api\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\base\ApiForm;
use common\helpers\Error as E;
use common\components\UserFriendlyRandomKey;
use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\models\EmailActivation;
use common\models\UsernameHistory;
use common\validators\EmailValidator;
use common\validators\LanguageValidator;
use common\validators\PasswordValidator;
use common\validators\UsernameValidator;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use const common\LATEST_RULES_VERSION;

class RegistrationForm extends ApiForm {

    public $captcha;

    public $username;

    public $email;

    public $password;

    public $rePassword;

    public $rulesAgreement;

    public $lang;

    public function rules() {
        return [
            ['captcha', ReCaptchaValidator::class],
            ['rulesAgreement', 'required', 'message' => E::RULES_AGREEMENT_REQUIRED],

            ['username', UsernameValidator::class],
            ['email', EmailValidator::class],

            ['password', 'required', 'message' => E::PASSWORD_REQUIRED],
            ['rePassword', 'required', 'message' => E::RE_PASSWORD_REQUIRED],
            ['password', PasswordValidator::class],
            ['rePassword', 'validatePasswordAndRePasswordMatch'],

            ['lang', LanguageValidator::class],
            ['lang', 'default', 'value' => 'en'],
        ];
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->password !== $this->rePassword) {
                $this->addError($attribute, E::RE_PASSWORD_DOES_NOT_MATCH);
            }
        }
    }

    /**
     * @return Account|null the saved model or null if saving fails
     * @throws Exception
     */
    public function signup() {
        if (!$this->validate() && !$this->canContinue($this->getFirstErrors())) {
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
            $account->rules_agreement_version = LATEST_RULES_VERSION;
            $account->setRegistrationIp(Yii::$app->request->getUserIP());
            if (!$account->save(false)) {
                throw new ErrorException('Account not created.');
            }

            $emailActivation = new RegistrationConfirmation();
            $emailActivation->account_id = $account->id;
            $emailActivation->key = UserFriendlyRandomKey::make();

            if (!$emailActivation->save()) {
                throw new ErrorException('Unable save email-activation model.');
            }

            $usernamesHistory = new UsernameHistory();
            $usernamesHistory->account_id = $account->id;
            $usernamesHistory->username = $account->username;
            $usernamesHistory->applied_in = $account->created_at;
            if (!$usernamesHistory->save()) {
                throw new ErrorException('Cannot save username history record');
            }

            $this->sendMail($emailActivation, $account);

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

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

        $htmlBody = Yii::$app->emailRenderer->getTemplate('register')
            ->setLocale($account->lang)
            ->setParams([
                'username' => $account->username,
                'code' => $emailActivation->key,
                'link' => Yii::$app->request->getHostInfo() . '/activation/' . $emailActivation->key,
            ])
            ->render();

        /** @var \yii\swiftmailer\Message $message */
        $message = $mailer->compose()
            ->setHtmlBody($htmlBody)
            ->setTo([$account->email => $account->username])
            ->setFrom([$fromEmail => 'Ely.by Accounts'])
            ->setSubject('Ely.by Account registration');

        if (!$message->send()) {
            throw new ErrorException('Unable send email with activation code.');
        }
    }

    /**
     * Метод проверяет, можно ли занять указанный при регистрации ник или e-mail. Так случается,
     * что пользователи вводят неправильный e-mail или ник, после замечают это и пытаются вновь
     * выпонить регистрацию. Мы не будем им мешать и просто удаляем существующие недозарегистрированные
     * аккаунты, позволяя им зарегистрироваться.
     *
     * @param array $errors массив, где ключ - это поле, а значение - первая ошибка из нашего
     * стандартного словаря ошибок
     *
     * @return bool
     */
    protected function canContinue(array $errors) : bool {
        if (ArrayHelper::getValue($errors, 'username') === E::USERNAME_NOT_AVAILABLE) {
            $duplicatedUsername = Account::findOne([
                'username' => $this->username,
                'status' => Account::STATUS_REGISTERED,
            ]);

            if ($duplicatedUsername !== null) {
                $duplicatedUsername->delete();
                unset($errors['username']);
            }
        }

        if (ArrayHelper::getValue($errors, 'email') === E::EMAIL_NOT_AVAILABLE) {
            $duplicatedEmail = Account::findOne([
                'email' => $this->email,
                'status' => Account::STATUS_REGISTERED,
            ]);

            if ($duplicatedEmail !== null) {
                $duplicatedEmail->delete();
                unset($errors['email']);
            }
        }

        return empty($errors);
    }

}
