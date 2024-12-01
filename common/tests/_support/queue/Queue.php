<?php
namespace common\tests\_support\queue;

use yii\base\NotSupportedException;
use yii\queue\Queue as BaseQueue;

class Queue extends BaseQueue {

    private $messages = [];

    public function __set($name, $value) {
        // Yii2 components may contains some configuration
        // But we just ignore it for this mock component
    }

    public function push($job): ?string {
        return (string)array_push($this->messages, $job);
    }

    public function status($id) {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    public function getMessages() {
        return $this->messages;
    }

    protected function pushMessage($message, $ttr, $delay, $priority): string {
        return '';
    }

}
