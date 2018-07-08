<?php
namespace api\modules\accounts\models;

use api\exceptions\ThisShouldNotHappenException;
use api\modules\internal\helpers\Error as E;
use common\models\Account;
use Yii;

class PardonAccountForm extends AccountActionForm {

    public function rules(): array {
        return [
            [['account'], 'validateAccountBanned'],
        ];
    }

    public function validateAccountBanned(): void {
        if ($this->getAccount()->status !== Account::STATUS_BANNED) {
            $this->addError('account', E::ACCOUNT_NOT_BANNED);
        }
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->status = Account::STATUS_ACTIVE;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot pardon account');
        }

        $transaction->commit();

        return true;
    }

}
