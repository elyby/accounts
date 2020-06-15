<?php
declare(strict_types=1);

namespace api\modules\accounts\models;

use api\modules\internal\helpers\Error as E;
use api\validators\PasswordRequiredValidator;
use common\models\Account;
use common\tasks\DeleteAccount;
use Webmozart\Assert\Assert;
use Yii;

final class DeleteAccountForm extends AccountActionForm {

    public $password;

    public function rules(): array {
        return [
            [['password'], PasswordRequiredValidator::class, 'account' => $this->getAccount()],
            [['account'], 'validateAccountActivity'],
        ];
    }

    public function validateAccountActivity(): void {
        if ($this->getAccount()->status === Account::STATUS_DELETED) {
            $this->addError('account', E::ACCOUNT_ALREADY_DELETED);
        }
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->status = Account::STATUS_DELETED;
        $account->deleted_at = time();
        Assert::true($account->save(), 'Cannot delete account');

        // Schedule complete account erasing
        Yii::$app->queue->delay($account->getDeleteAt()->diffInRealSeconds())->push(new DeleteAccount($account->id));

        $transaction->commit();

        return true;
    }

}
