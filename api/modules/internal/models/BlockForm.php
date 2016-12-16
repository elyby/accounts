<?php
namespace api\modules\internal\models;

use api\models\base\ApiForm;
use common\helpers\Amqp;
use common\models\Account;
use common\models\amqp\AccountBanned;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\ErrorException;

class BlockForm extends ApiForm {

    const DURATION_FOREVER = -1;

    /**
     * Нереализованный функционал блокировки аккаунта на определённый период времени.
     * Сейчас установка этого параметра ничего не даст, аккаунт будет заблокирован навечно,
     * но по задумке, здесь необходимо передать количество секунд, на которое будет
     * заблокирован аккаунт пользователя.
     *
     * @var int
     */
    public $duration = self::DURATION_FOREVER;

    /**
     * Нереализованный функционал указания причины блокировки аккаунта.
     *
     * @var string
     */
    public $message = '';

    /**
     * @var Account
     */
    private $account;

    public function rules() {
        return [
            [['duration'], 'integer', 'min' => self::DURATION_FOREVER],
            [['message'], 'string'],
        ];
    }

    public function getAccount(): Account {
        return $this->account;
    }

    public function ban(): bool {
        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->account;
        $account->status = Account::STATUS_BANNED;
        if (!$account->save()) {
            throw new ErrorException('Cannot ban account');
        }

        $this->createTask();

        $transaction->commit();

        return true;
    }

    public function createTask() {
        $model = new AccountBanned();
        $model->accountId = $this->account->id;
        $model->duration = $this->duration;
        $model->message = $this->message;

        $message = Amqp::getInstance()->prepareMessage($model, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        Amqp::sendToEventsExchange('accounts.account-banned', $message);
    }

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

}
