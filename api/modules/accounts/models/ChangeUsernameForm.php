<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\validators\PasswordRequiredValidator;
use common\models\UsernameHistory;
use common\tasks\PullMojangUsername;
use common\validators\UsernameValidator;
use Webmozart\Assert\Assert;
use Yii;

class ChangeUsernameForm extends AccountActionForm {

    public $username;

    public $password;

    public function rules(): array {
        return [
            ['username', UsernameValidator::class, 'accountCallback' => function() {
                return $this->getAccount()->id;
            }],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount()],
        ];
    }

    /**
     * @CollectModelMetrics(prefix="accounts.changeUsername")
     */
    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        if ($this->username === $account->username) {
            return true;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account->username = $this->username;
        Assert::true($account->save(), 'Cannot save account model with new username');

        $usernamesHistory = new UsernameHistory();
        $usernamesHistory->account_id = $account->id;
        $usernamesHistory->username = $account->username;
        Assert::true($usernamesHistory->save(), 'Cannot save username history record');

        Yii::$app->queue->push(PullMojangUsername::createFromAccount($account));

        $transaction->commit();

        return true;
    }

}
