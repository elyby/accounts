<?php
namespace api\modules\accounts\models;

use api\exceptions\ThisShouldNotHappenException;
use api\modules\internal\helpers\Error as E;
use common\models\Account;
use common\tasks\ClearAccountSessions;
use Yii;

class BanAccountForm extends AccountActionForm {

    public const DURATION_FOREVER = -1;

    /**
     * Unimplemented account blocking functionality for a certain period of time.
     * Setting this parameter currently will do nothing, the account will be blocked forever,
     * but the idea is to pass the number of seconds for which the user's account will be blocked.
     *
     * @var int
     */
    public $duration = self::DURATION_FOREVER;

    /**
     * Unimplemented functionality to indicate the reason for account blocking.
     *
     * @var string
     */
    public $message = '';

    public function rules(): array {
        return [
            [['duration'], 'integer', 'min' => self::DURATION_FOREVER],
            [['message'], 'string'],
            [['account'], 'validateAccountActivity'],
        ];
    }

    public function validateAccountActivity(): void {
        if ($this->getAccount()->status === Account::STATUS_BANNED) {
            $this->addError('account', E::ACCOUNT_ALREADY_BANNED);
        }
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->status = Account::STATUS_BANNED;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot ban account');
        }

        Yii::$app->queue->push(ClearAccountSessions::createFromAccount($account));

        $transaction->commit();

        return true;
    }

}
