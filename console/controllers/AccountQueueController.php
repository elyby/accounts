<?php
namespace console\controllers;

use common\components\RabbitMQ\Component as RabbitMQComponent;
use console\controllers\base\AmqpController;

class AccountQueueController extends AmqpController {

    public function getExchangeName() {
        return 'account';
    }

    public function getQueueName() {
        return 'account-operations';
    }

    public function getExchangeDeclareArgs() {
        return array_replace(parent::getExchangeDeclareArgs(), [
            1 => RabbitMQComponent::TYPE_DIRECT, // exchange-type -> direct
            3 => false, // no-ack -> false
        ]);
    }

    public function getQueueBindArgs($exchangeName, $queueName) {
        return [$exchangeName, $queueName, '#']; // Мы хотим получать сюда все события по аккаунту
    }

    public function routeChangeUsername($body) {
        // TODO: implement this
    }

}
