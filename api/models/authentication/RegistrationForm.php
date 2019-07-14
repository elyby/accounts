<?php
namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\base\ApiForm;
use common\components\UserFriendlyRandomKey;
use common\helpers\Error as E;
use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\models\UsernameHistory;
use common\tasks\SendRegistrationEmail;
use common\validators\EmailValidator;
use common\validators\LanguageValidator;
use common\validators\PasswordValidator;
use common\validators\UsernameValidator;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\ErrorException;
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
     * @CollectModelMetrics(prefix="signup.register")
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
            if (!$account->save()) {
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

            Yii::$app->queue->push(SendRegistrationEmail::createFromConfirmation($emailActivation));

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $account;
    }

    /**
     * The method checks whether the username or E-mail specified during registration
     * can be occupied. It happens that users enter the wrong E-mail or username,
     * then notice it and try to re-register. We'll not interfere with them
     * and simply delete existing not-finished-registration account,
     * allowing them to take it.
     *
     * @param array $errors an array where the key is a field and the value is
     *                      the first error from our standard error dictionary
     * @return bool
     */
    protected function canContinue(array $errors): bool {
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
