<?php
namespace common\components\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use ReflectionMethod;
use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Inflector;
use yii\helpers\Json;

abstract class Controller extends \yii\console\Controller {
    use MessageTrait;

    const MESSAGE_INFO = 0;
    const MESSAGE_ERROR = 1;

    public $defaultAction = 'run';

    public function actionRun() {
        $this->configureListen();
    }

    /**
     * Имя exchange, который будет прослушивать этот интерпретатор
     *
     * @return string
     */
    abstract public function getExchangeName();

    /**
     * Есть метод вернёт null, то будет создана временная очередь, которая будет автоматически удалена
     * после завершения процесса её обработчика
     *
     * @return null|string
     */
    public function getQueueName() {
        return null;
    }

    /**
     * @return Component
     */
    protected function getAmqp() {
        return Yii::$app->get('amqp');
    }

    protected function configureListen() {
        $exchangeName = $this->getExchangeName();
        $connection = $this->getAmqp()->getConnection();
        $channel = $this->getAmqp()->getChannel();
        call_user_func_array([$channel, 'exchange_declare'], $this->getExchangeDeclareArgs());
        list($queueName) = call_user_func_array([$channel, 'queue_declare'], $this->getQueueDeclareArgs());
        // TODO: нужно продумать механизм для подписки на множество роутов
        call_user_func_array([$channel, 'queue_bind'], $this->getQueueBindArgs($exchangeName, $queueName));
        call_user_func_array([$channel, 'basic_consume'], $this->getBasicConsumeArgs($queueName));
        $channel->basic_qos(null, 1, true);

        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    public function callback(AMQPMessage $msg) {
        $routingKey = $msg->delivery_info['routing_key'];
        $method = 'route' . Inflector::camelize($routingKey);
        $body = Json::decode($msg->body, true);

        if (!method_exists($this, $method)) {
            $this->log(
                sprintf('Unknown routing key "%s" for exchange "%s".', $routingKey, $this->getExchangeName()),
                static::MESSAGE_ERROR
            );

            $this->log(
                print_r($body, true),
                static::MESSAGE_INFO
            );
        }

        // Инверсия значения, т.к. параметр называется no_ack, то есть уже инвертирован
        $isAckRequired = !ArrayHelper::getValue($this->getBasicConsumeArgs($this->getQueueName()), 3, true);
        $result = $this->getResult($method, $body, $msg);
        if ($isAckRequired) {
            if ($result === false) {
                $this->reject($msg, true);
            } else {
                $this->ack($msg);
            }
        }
    }

    private function getResult($method, $body, $msg) {
        try {
            $result = $this->$method($this->prepareArguments($method, $body), $msg);
        } catch(Exception $e) {
            if (strstr($e->getMessage(), '2006 MySQL server has gone away') !== false) {
                Console::output(Console::ansiFormat('Server gone away, try to reconnect', [Console::FG_GREY]));
                Yii::$app->db->close();
                Yii::$app->db->open();
                Console::output(Console::ansiFormat('recall method', [Console::FG_GREY]));
                $result = $this->$method($body, $msg);
            } else {
                throw $e;
            }
        }

        return $result;
    }

    private function prepareArguments($methodName, $body) {
        $method = new ReflectionMethod($this, $methodName);
        $parameters = $method->getParameters();
        if (!isset($parameters[0])) {
            return $body;
        }

        $bodyParam = $parameters[0];
        if (PHP_MAJOR_VERSION === 7) {
            // TODO: логика для php7 не тестировалась, так то не факт, что оно взлетит на php7
            if (!$bodyParam->hasType() || $bodyParam->isArray()) {
                return $body;
            }

            $type = (string)$bodyParam->getType();
            $object = new $type;
        } else {
            $class = $bodyParam->getClass();
            if ($class === null) {
                return $body;
            }

            $type = $class->name;
            $object = new $type;
        }

        return Yii::configure($object, $body);
    }

    /**
     * Список аргументов, с которым будет вызван метод \PhpAmqpLib\Channel\AMQPChannel::exchange_declare()
     * По умолчанию создаётся очередь с типом fanout. Кроме того, в отличие от стандартных аргументов,
     * здесь указано, что auto_delete в false состоянии
     *
     * @return array
     */
    protected function getExchangeDeclareArgs() {
        return [$this->getExchangeName(), Component::TYPE_FANOUT, false, false, false];
    }

    /**
     * Список аргументов, с которым будет вызван метод \PhpAmqpLib\Channel\AMQPChannel::queue_declare()
     *
     * Если метод getQueueName() не переопределён и в нём не задано имя очереди, то будет создана
     * временная очередь, которая будет автоматически удалена после завершения работы всех Consumer'ов
     * Если же есть фиксированное имя очереди, то она будет создана с аргументом
     * auto_delete в false (4 индекс массива)
     *
     * @return array
     */
    protected function getQueueDeclareArgs() {
        $queueName = $this->getQueueName();
        if ($queueName === null) {
            return [];
        } else {
            return [$queueName, false, false, false, false];
        }
    }

    /**
     * Список аргументов, с которым будет вызван метод \PhpAmqpLib\Channel\AMQPChannel::queue_bind()
     *
     * @param string $exchangeName
     * @param string $queueName
     * @return array
     */
    protected function getQueueBindArgs($exchangeName, $queueName) {
        return [$queueName, $exchangeName];
    }

    /**
     * Список аргументов, с которым будет вызван метод \PhpAmqpLib\Channel\AMQPChannel::basic_consume()
     * По умолчанию здесь находятся стандартные аргументы для этого метода
     *
     * @param string $queueName
     * @return array
     */
    protected function getBasicConsumeArgs($queueName) {
        return [$queueName, '', false, false, false, false, [$this, 'callback']];
    }

    /**
     * Logs info and error messages.
     *
     * TODO: что-то мне подсказывает, что ему тут не место
     *
     * @param $message
     * @param $type
     */
    protected function log($message, $type = self::MESSAGE_INFO) {
        $format = [$type == self::MESSAGE_ERROR ? Console::FG_RED : Console::FG_BLUE];
        Console::output(Console::ansiFormat($message, $format));
    }

}
