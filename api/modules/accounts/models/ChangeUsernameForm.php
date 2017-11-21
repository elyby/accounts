<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\exceptions\ThisShouldNotHappenException;
use api\validators\PasswordRequiredValidator;
use common\helpers\Amqp;
use common\models\amqp\UsernameChanged;
use common\models\UsernameHistory;
use common\validators\UsernameValidator;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

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

        $oldNickname = $account->username;
        $account->username = $this->username;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot save account model with new username');
        }

        $usernamesHistory = new UsernameHistory();
        $usernamesHistory->account_id = $account->id;
        $usernamesHistory->username = $account->username;
        if (!$usernamesHistory->save()) {
            throw new ErrorException('Cannot save username history record');
        }

        $this->createEventTask($account->id, $account->username, $oldNickname);

        $transaction->commit();

        return true;
    }

    /**
     * TODO: вынести это в отдельную сущность, т.к. эта команда используется внутри формы регистрации
     *
     * @param integer $accountId
     * @param string  $newNickname
     * @param string  $oldNickname
     *
     * @throws \PhpAmqpLib\Exception\AMQPExceptionInterface|\yii\base\Exception
     */
    public function createEventTask($accountId, $newNickname, $oldNickname): void {
        $model = new UsernameChanged();
        $model->accountId = $accountId;
        $model->oldUsername = $oldNickname;
        $model->newUsername = $newNickname;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.username-changed', $message);
    }

}
