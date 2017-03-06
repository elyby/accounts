<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\validators\PasswordValidator;
use Yii;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

class ChangePasswordForm extends ApiForm {

    public $newPassword;

    public $newRePassword;

    public $logoutAll;

    public $password;

    /**
     * @var \common\models\Account
     */
    private $_account;

    public function __construct(Account $account, array $config = []) {
        $this->_account = $account;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return ArrayHelper::merge(parent::rules(), [
            ['newPassword', 'required', 'message' => E::NEW_PASSWORD_REQUIRED],
            ['newRePassword', 'required', 'message' => E::NEW_RE_PASSWORD_REQUIRED],
            ['newPassword', PasswordValidator::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
            ['logoutAll', 'boolean'],
            ['password', PasswordRequiredValidator::class, 'account' => $this->_account],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors($attribute)) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, E::NEW_RE_PASSWORD_DOES_NOT_MATCH);
            }
        }
    }

    /**
     * @return bool
     * @throws ErrorException
     */
    public function changePassword() : bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        $account = $this->_account;
        $account->setPassword($this->newPassword);

        if ($this->logoutAll) {
            Yii::$app->user->terminateSessions();
        }

        if (!$account->save()) {
            throw new ErrorException('Cannot save user model');
        }

        $transaction->commit();

        return true;
    }

    protected function getAccount() : Account {
        return $this->_account;
    }

}
