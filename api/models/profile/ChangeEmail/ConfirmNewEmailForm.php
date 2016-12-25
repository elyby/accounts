<?php
namespace api\models\profile\ChangeEmail;

use api\models\base\ApiForm;
use api\validators\EmailActivationKeyValidator;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\EmailChanged;
use common\models\EmailActivation;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class ConfirmNewEmailForm extends ApiForm {

    public $key;

    /**
     * @var Account
     */
    private $account;

    public function rules() {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION],
        ];
    }

    /**
     * @return Account
     */
    public function getAccount(): Account {
        return $this->account;
    }

    public function changeEmail(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\NewEmailConfirmation $activation */
        $activation = $this->key;
        $activation->delete();

        $account = $this->getAccount();
        $oldEmail = $account->email;
        $account->email = $activation->newEmail;
        if (!$account->save()) {
            throw new ErrorException('Cannot save new account email value');
        }

        $this->createTask($account->id, $account->email, $oldEmail);

        $transaction->commit();

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

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

}
