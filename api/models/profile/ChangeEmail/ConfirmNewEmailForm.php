<?php
namespace api\models\profile\ChangeEmail;

use api\models\base\KeyConfirmationForm;
use common\models\Account;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;

class ConfirmNewEmailForm extends KeyConfirmationForm {

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    /**
     * @return Account
     */
    public function getAccount() {
        return $this->account;
    }

    public function changeEmail() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            /** @var \common\models\confirmations\NewEmailConfirmation $activation */
            $activation = $this->getActivationCodeModel();
            $activation->delete();

            $account = $this->getAccount();
            $account->email = $activation->newEmail;
            if (!$account->save()) {
                throw new ErrorException('Cannot save new account email value');
            }

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

}
