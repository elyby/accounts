<?php
namespace api\models\profile\ChangeEmail;

use api\models\base\KeyConfirmationForm;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\EmailChanged;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class ConfirmNewEmailForm extends KeyConfirmationForm {

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    /**
     * @return Account
     */
    public function getAccount() : Account {
        return $this->account;
    }

    public function changeEmail() : bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            /** @var \common\models\confirmations\NewEmailConfirmation $activation */
            $activation = $this->getActivationCodeModel();
            $activation->delete();

            $account = $this->getAccount();
            $oldEmail = $account->email;
            $account->email = $activation->newEmail;
            if (!$account->save()) {
                throw new ErrorException('Cannot save new account email value');
            }

            $this->createTask($account->id, $account->email, $oldEmail);

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * @param integer $accountId
     * @param string  $newEmail
     * @param string  $oldEmail
     * @throws \PhpAmqpLib\Exception\AMQPExceptionInterface
     */
    public function createTask($accountId, $newEmail, $oldEmail) {
        $model = new EmailChanged;
        $model->accountId = $accountId;
        $model->oldEmail = $oldEmail;
        $model->newEmail = $newEmail;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.email-changed', $message);
    }

}
