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

    public static function sendToEventsExchange($routingKey, $message) {
        static::sendToExchange('events', $routingKey, $message, [
            1 => Component::TYPE_TOPIC, // type -> topic
            3 => true, // durable -> true
        ]);
    }

}
