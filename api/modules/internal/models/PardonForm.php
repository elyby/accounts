<?php
namespace api\modules\internal\models;

use api\models\base\ApiForm;
use api\modules\internal\helpers\Error as E;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\AccountPardoned;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class PardonForm extends ApiForm {

    /**
     * @var Account
     */
    private $account;

    public function rules(): array {
        return [
            [['account'], 'validateAccountBanned'],
        ];
    }

    public function getAccount(): Account {
        return $this->account;
    }

    public function validateAccountBanned(): void {
        if ($this->account->status !== Account::STATUS_BANNED) {
            $this->addError('account', E::ACCOUNT_NOT_BANNED);
        }
    }

    public function pardon(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->account;
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
        $model->accountId = $this->account->id;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.account-pardoned', $message);
    }

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

}
