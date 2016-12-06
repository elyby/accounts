<?php
namespace tests\codeception\common\_support\amqp;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\Yii2;

class Helper extends Module {

    /**
     * Checks that message is created.
     *
     * ```php
     * <?php
     * // check that at least 1 message was created
     * $I->seeAmqpMessageIsCreated();
     *
     * // check that only 3 messages were created
     * $I->seeAmqpMessageIsCreated(3);
     * ```
     *
     * @param string|null $exchange
     * @param int|null    $num
     */
    public function seeAmqpMessageIsCreated($exchange = null, $num = null) {
        if ($num === null) {
            $this->assertNotEmpty($this->grabSentAmqpMessages($exchange), 'message were created');
            return;
        }

        // TODO: заменить на assertCount() после релиза Codeception 2.2.7
        // https://github.com/Codeception/Codeception/pull/3802
        /** @noinspection PhpUnitTestsInspection */
        $this->assertEquals(
            $num,
            count($this->grabSentAmqpMessages($exchange)),
            'number of created messages is equal to ' . $num
        );
    }

    /**
     * Checks that no messages was created
     *
     * @param string|null $exchange
     */
    public function dontSeeAmqpMessageIsCreated($exchange = null) {
        $this->seeAmqpMessageIsCreated($exchange, 0);
    }

    /**
     * Returns last sent message
     *
     * @param string|null $exchange
     * @return \PhpAmqpLib\Message\AMQPMessage
     */
    public function grabLastSentAmqpMessage($exchange = null) {
        $this->seeAmqpMessageIsCreated();
        $messages = $this->grabSentAmqpMessages($exchange);

        return end($messages);
    }

    /**
     * Returns array of all sent amqp messages.
     * Each message is `\PhpAmqpLib\Message\AMQPMessage` instance.
     * Useful to perform additional checks using `Asserts` module.
     *
     * @param string|null $exchange
     * @return \PhpAmqpLib\Message\AMQPMessage[]
     * @throws ModuleException
     */
    public function grabSentAmqpMessages($exchange = null) {
        $amqp = $this->grabComponent('amqp');
        if (!$amqp instanceof TestComponent) {
            throw new ModuleException($this, 'AMQP module is not mocked, can\'t test messages');
        }

        return $amqp->getSentMessages($exchange);
    }

    private function grabComponent(string $component) {
        return $this->getYii2()->grabComponent($component);
    }

    private function getYii2() : Yii2 {
        $yii2 = $this->getModule('Yii2');
        if (!$yii2 instanceof Yii2) {
            throw new ModuleException($this, 'Yii2 module must be configured');
        }

        return $yii2;
    }

}
