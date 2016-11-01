<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\validators\PasswordValidate;
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
            ['newPassword', PasswordValidate::class],
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
            /** @var \api\components\User\Component $userComponent */
            $userComponent = Yii::$app->user;
            $sessions = $account->sessions;
            $activeSession = $userComponent->getActiveSession();
            foreach ($sessions as $session) {
                if (!$activeSession || $activeSession->id !== $session->id) {
                    $session->delete();
                }
            }
        }

        if (!$account->save(false)) {
            throw new ErrorException('Cannot save user model');
        }

        $transaction->commit();

        return true;
    }

    protected function getAccount() : Account {
        return $this->_account;
    }

}
