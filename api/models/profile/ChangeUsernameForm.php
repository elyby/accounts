<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use api\validators\PasswordRequiredValidator;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\UsernameChanged;
use common\models\UsernameHistory;
use common\validators\UsernameValidator;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class ChangeUsernameForm extends ApiForm {

    public $username;

    public $password;

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        parent::__construct($config);
        $this->account = $account;
    }

    public function rules(): array {
        return [
            ['username', UsernameValidator::class, 'accountCallback' => function() {
                return $this->account->id;
            }],
            ['password', PasswordRequiredValidator::class],
        ];
    }

    public function change(): bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->account;
        if ($this->username === $account->username) {
            return true;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $oldNickname = $account->username;
            $account->username = $this->username;
            if (!$account->save()) {
                throw new ErrorException('Cannot save account model with new username');
            }

            $usernamesHistory = new UsernameHistory();
            $usernamesHistory->account_id = $account->id;
            $usernamesHistory->username = $account->username;
            if (!$usernamesHistory->save()) {
                throw new ErrorException('Cannot save username history record');
            }

            $this->createEventTask($account->id, $account->username, $oldNickname);

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * TODO: вынести это в отдельную сущность, т.к. эта команда используется внутри формы регистрации
     *
     * @param integer $accountId
     * @param string  $newNickname
     * @param string  $oldNickname
     * @throws \PhpAmqpLib\Exception\AMQPExceptionInterface
     */
    public function createEventTask($accountId, $newNickname, $oldNickname) {
        $model = new UsernameChanged;
        $model->accountId = $accountId;
        $model->oldUsername = $oldNickname;
        $model->newUsername = $newNickname;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.username-changed', $message);
    }

}
