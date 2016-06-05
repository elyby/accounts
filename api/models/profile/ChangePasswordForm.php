<?php
namespace api\models\profile;

use api\models\base\PasswordProtectedForm;
use common\models\Account;
use common\validators\PasswordValidate;
use Yii;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

class ChangePasswordForm extends PasswordProtectedForm {

    public $newPassword;

    public $newRePassword;

    public $logoutAll;

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
            [['newPassword', 'newRePassword'], 'required', 'message' => 'error.{attribute}_required'],
            ['newPassword', PasswordValidate::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
            ['logoutAll', 'boolean'],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors($attribute)) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, 'error.newRePassword_does_not_match');
            }
        }
    }

    /**
     * @return boolean
     */
    public function changePassword() {
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

        if (!$account->save()) {
            throw new ErrorException('Cannot save user model');
        }

        $transaction->commit();

        return true;
    }

    protected function getAccount() {
        return $this->_account;
    }

}
