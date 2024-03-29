<?php
declare(strict_types=1);

namespace api\models\authentication;

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

    public function recoverPassword(): ?AuthenticationResult {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\ForgotPassword $confirmModel */
        $confirmModel = $this->key;
        $account = $confirmModel->account;
        $account->password = $this->newPassword;
        /** @noinspection PhpUnhandledExceptionInspection */
        Assert::notSame($confirmModel->delete(), false, 'Unable remove activation key.');

        Assert::true($account->save(), 'Unable activate user account.');

        $token = Yii::$app->tokensFactory->createForWebAccount($account);

        $transaction->commit();

        return new AuthenticationResult($token);
    }

}
