<?php
namespace api\modules\accounts\models;

use api\validators\EmailActivationKeyValidator;
use common\helpers\Amqp;
use common\models\amqp\EmailChanged;
use common\models\EmailActivation;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class ChangeEmailForm extends AccountActionForm {

    public $key;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION],
        ];
    }

    public function performAction(): bool {
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

    public function createTask(int $accountId, string $newEmail, string $oldEmail): void {
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
