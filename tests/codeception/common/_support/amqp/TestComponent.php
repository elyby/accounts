<?php
namespace tests\codeception\common\_support\amqp;

use common\components\RabbitMQ\Component;
use PhpAmqpLib\Connection\AbstractConnection;

class TestComponent extends Component {

    private $sentMessages = [];

    public function init() {
        \yii\base\Component::init();
    }

    public function getConnection() {
        /** @noinspection MagicMethodsValidityInspection */
        /** @noinspection PhpMissingParentConstructorInspection */
        return new class extends AbstractConnection {
            public function __construct(
                $user,
                $password,
                $vhost,
                $insist,
                $login_method,
                $login_response,
                $locale,
                \PhpAmqpLib\Wire\IO\AbstractIO $io,
                $heartbeat
            ) {
                // ничего не делаем
            }
        };
    }

    public function sendToExchange($exchangeName, $routingKey, $message, $exchangeArgs = [], $publishArgs = []) {
        $this->sentMessages[$exchangeName][] = $this->prepareMessage($message);
    }

    /**
     * @param string|null $exchangeName
     * @return \PhpAmqpLib\Message\AMQPMessage[]
     */
    public function getSentMessages(string $exchangeName = null) : array {
        if ($exchangeName !== null) {
            return $this->sentMessages[$exchangeName] ?? [];
        }

        $messages = [];
        foreach($this->sentMessages as $exchangeGroup) {
            foreach ($exchangeGroup as $message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

}
