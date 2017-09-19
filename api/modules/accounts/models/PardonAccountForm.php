<?php
namespace api\modules\accounts\models;

use api\modules\internal\helpers\Error as E;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\AccountPardoned;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

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
            throw new ErrorException('Cannot pardon account');
        }

        $this->createTask();

        $transaction->commit();

        return true;
    }

    public function createTask(): void {
        $model = new AccountPardoned();
        $model->accountId = $this->getAccount()->id;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.account-pardoned', $message);
    }

}
