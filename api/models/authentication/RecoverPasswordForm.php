<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\models\base\ApiForm;
use api\validators\EmailActivationKeyValidator;
use common\helpers\Error as E;
use common\models\EmailActivation;
use common\validators\PasswordValidator;
use Webmozart\Assert\Assert;
use Yii;

class RecoverPasswordForm extends ApiForm {

    public $key;

    public $newPassword;

    public $newRePassword;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY],
            ['newPassword', 'required', 'message' => E::NEW_PASSWORD_REQUIRED],
            ['newRePassword', 'required', 'message' => E::NEW_RE_PASSWORD_REQUIRED],
            ['newPassword', PasswordValidator::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ];
    }

    public function validatePasswordAndRePasswordMatch(string $attribute): void {
        if (!$this->hasErrors() && $this->newPassword !== $this->newRePassword) {
            $this->addError($attribute, E::NEW_RE_PASSWORD_DOES_NOT_MATCH);
        }
    }

    /**
     * @CollectModelMetrics(prefix="authentication.recoverPassword")
     * @return \api\components\User\AuthenticationResult|bool
     * @throws \Throwable
     */
    public function recoverPassword() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\ForgotPassword $confirmModel */
        $confirmModel = $this->key;
        $account = $confirmModel->account;
        $account->password = $this->newPassword;
        Assert::notSame($confirmModel->delete(), false, 'Unable remove activation key.');

        Assert::true($account->save(), 'Unable activate user account.');

        $token = Yii::$app->user->createJwtAuthenticationToken($account);
        $jwt = Yii::$app->user->serializeToken($token);

        $transaction->commit();

        return new \api\components\User\AuthenticationResult($account, $jwt, null);
    }

}
