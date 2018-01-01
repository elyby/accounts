<?php
namespace tests\codeception\common\_support\queue;

use yii\base\NotSupportedException;
use yii\queue\Queue as BaseQueue;

class Queue extends BaseQueue {

    private $messages = [];

    public function push($job) {
        $this->messages[] = $job;
    }

    public function status($id) {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    public function getMessages() {
        return $this->messages;
    }

    protected function pushMessage($message, $ttr, $delay, $priority) {
        // This function is abstract, but will be not called
    }

    public function __set($name, $value) {
        // Yii2 components may contains some configuration
        // But we just ignore it for this mock component
    }

}
