<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\components\User\Component;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\validators\PasswordValidator;
use Webmozart\Assert\Assert;
use Yii;
use yii\helpers\ArrayHelper;

class ChangePasswordForm extends AccountActionForm {

    public $newPassword;

    public $newRePassword;

    public $logoutAll;

    public $password;

    /**
     * @inheritdoc
     */
    public function rules(): array {
        return ArrayHelper::merge(parent::rules(), [
            ['newPassword', 'required', 'message' => E::NEW_PASSWORD_REQUIRED],
            ['newRePassword', 'required', 'message' => E::NEW_RE_PASSWORD_REQUIRED],
            ['newPassword', PasswordValidator::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
            ['logoutAll', 'boolean'],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount(), 'when' => function() {
                return !$this->hasErrors();
            }],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute): void {
        if (!$this->hasErrors($attribute)) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, E::NEW_RE_PASSWORD_DOES_NOT_MATCH);
            }
        }
    }

    /**
     * @CollectModelMetrics(prefix="accounts.changePassword")
     */
    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->setPassword($this->newPassword);

        if ($this->logoutAll) {
            Yii::$app->user->terminateSessions($account, Component::KEEP_CURRENT_SESSION);
        }

        Assert::true($account->save(), 'Cannot save user model');

        $transaction->commit();

        return true;
    }

}
