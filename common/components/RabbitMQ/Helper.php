<?php
namespace common\components\RabbitMQ;

use Yii;

class Helper {

    /**
     * @return Component $amqp
     */
    public static function getInstance() {
        return Yii::$app->get('amqp');
    }

    public static function sendToExchange($exchange, $routingKey, $message, $exchangeArgs = []) {
        static::getInstance()->sendToExchange($exchange, $routingKey, $message, $exchangeArgs);
    }

}
