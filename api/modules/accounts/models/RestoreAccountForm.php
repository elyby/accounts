<?php
declare(strict_types=1);

namespace api\modules\accounts\models;

use api\modules\internal\helpers\Error as E;
use common\models\Account;
use Webmozart\Assert\Assert;
use Yii;

final class RestoreAccountForm extends AccountActionForm {

    public function rules(): array {
        return [
            [['account'], 'validateAccountActivity'],
        ];
    }

    public function validateAccountActivity(): void {
        if ($this->getAccount()->status !== Account::STATUS_DELETED) {
            $this->addError('account', E::ACCOUNT_NOT_DELETED);
        }
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->status = Account::STATUS_ACTIVE;
        $account->deleted_at = null;
        Assert::true($account->save(), 'Cannot restore account');

        $transaction->commit();

        return true;
    }

}
