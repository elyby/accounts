<?php
namespace api\models;

use api\models\base\PasswordProtectedForm;
use common\helpers\Amqp;
use common\models\amqp\UsernameChanged;
use common\models\UsernameHistory;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

class ChangeUsernameForm extends PasswordProtectedForm {

    public $username;

    public function rules() {
        return ArrayHelper::merge(parent::rules(), [
            [['username'], 'required', 'message' => 'error.{attribute}_required'],
            [['username'], 'validateUsername'],
        ]);
    }

    public function validateUsername($attribute) {
        $account = $this->getAccount();
        $account->username = $this->$attribute;
        if (!$account->validate(['username'])) {
            $this->addErrors($account->getErrors());
        }
    }

    public function change() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        $account = $this->getAccount();
        $oldNickname = $account->username;
        try {
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

            $transaction->commit();
        } catch (ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->createTask($account->id, $account->username, $oldNickname);

        return true;
    }

    /**
     * TODO: вынести это в отдельную сущность, т.к. эта команда используется внутри формы регистрации
     *
     * @param integer $accountId
     * @param string  $newNickname
     * @param string  $oldNickname
     */
    public function createTask($accountId, $newNickname, $oldNickname) {
        $message = Amqp::getInstance()->prepareMessage(new UsernameChanged([
            'accountId' => $accountId,
            'oldUsername' => $oldNickname,
            'newUsername' => $newNickname,
        ]), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToExchange('account', 'username-changed', $message, [
            3 => true, // durable -> true
        ]);
    }

}
