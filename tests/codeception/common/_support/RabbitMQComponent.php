<?php
namespace tests\codeception\common\_support;

use common\components\RabbitMQ\Component;
use PhpAmqpLib\Connection\AbstractConnection;

class RabbitMQComponent extends Component {

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
        // ничего не делаем
    }

}
