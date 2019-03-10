<?php
namespace common\tests\_support\queue;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\Yii2;

class CodeceptionQueueHelper extends Module {

    /**
     * Returns last sent message
     *
     * @return \yii\queue\JobInterface|null
     */
    public function grabLastQueuedJob() {
        $messages = $this->grabQueueJobs();
        $last = end($messages);
        if ($last === false) {
            return null;
        }

        return $last;
    }

    /**
     * Returns array of all sent amqp messages.
     * Each message is `\PhpAmqpLib\Message\AMQPMessage` instance.
     * Useful to perform additional checks using `Asserts` module.
     *
     * @param string|null $exchange
     * @return \yii\queue\JobInterface[]
     * @throws ModuleException
     */
    public function grabQueueJobs() {
        $amqp = $this->grabComponent('queue');
        if (!$amqp instanceof Queue) {
            throw new ModuleException($this, 'AMQP module is not mocked, can\'t test messages');
        }

        return $amqp->getMessages();
    }

    private function grabComponent(string $component) {
        return $this->getYii2()->grabComponent($component);
    }

    private function getYii2(): Yii2 {
        $yii2 = $this->getModule('Yii2');
        if (!$yii2 instanceof Yii2) {
            throw new ModuleException($this, 'Yii2 module must be configured');
        }

        return $yii2;
    }

}
