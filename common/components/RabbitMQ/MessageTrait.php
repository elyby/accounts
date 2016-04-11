<?php
namespace common\components\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

trait MessageTrait {

    public function ack(AMQPMessage $msg) {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    public function reject(AMQPMessage $msg, $requeue = true) {
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], $requeue);
    }

}
