<?php
namespace common\components\RabbitMQ;

use yii\base\Exception;
use yii\helpers\Json;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Не гибкий компонент для работы с RabbitMQ, заточенный под нужны текущего проекта
 *
 * Компонент основан на расширении Alexey Kuznetsov <mirakuru@webtoucher.ru>
 *
 * @property AMQPStreamConnection $connection AMQP connection.
 * @property AMQPChannel $channel AMQP channel.
 */
class Component extends \yii\base\Component {

    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_HEADERS = 'headers';
    const TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPStreamConnection
     */
    protected $amqpConnection;

    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    /**
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * @var integer
     */
    public $port = 5672;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $vhost = '/';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        if (empty($this->user)) {
            throw new Exception("Parameter 'user' was not set for AMQP connection.");
        }
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection() {
        if (!$this->amqpConnection) {
            $this->amqpConnection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
        }

        return $this->amqpConnection;
    }

    /**
     * @param string $channel_id
     * @return AMQPChannel
     */
    public function getChannel($channel_id = null) {
        $index = $channel_id ?: 'default';
        if (!array_key_exists($index, $this->channels)) {
            $this->channels[$index] = $this->getConnection()->channel($channel_id);
        }

        return $this->channels[$index];
    }

    // TODO: метод sendToQueue

    /**
     * Sends message to the exchange.
     *
     * @param string       $exchangeName
     * @param string       $routingKey
     * @param string|array $message
     * @param array        $exchangeArgs
     * @param array        $publishArgs
     */
    public function sendToExchange($exchangeName, $routingKey, $message, $exchangeArgs = [], $publishArgs = []) {
        $message = $this->prepareMessage($message);
        $channel = $this->getChannel();
        $channel->exchange_declare(...$this->prepareExchangeArgs($exchangeName, $exchangeArgs));
        $channel->basic_publish(...$this->preparePublishArgs($message, $exchangeName, $routingKey, $publishArgs));
    }

    /**
     * Объединяет переданный набор аргументов с поведением по умолчанию
     *
     * @param string $exchangeName
     * @param array $args
     * @return array
     */
    protected function prepareExchangeArgs($exchangeName, array $args) {
        return array_replace([
            $exchangeName,
            self::TYPE_FANOUT,
            false,
            false,
            false,
        ], $args);
    }

    /**
     * Объединяет переданный набор аргументов с поведением по умолчанию
     *
     * @param AMQPMessage $message
     * @param string      $exchangeName
     * @param string      $routeKey
     * @param array       $args
     *
     * @return array
     */
    protected function preparePublishArgs($message, $exchangeName, $routeKey, array $args) {
        return array_replace([
            $message,
            $exchangeName,
            $routeKey,
        ], $args);
    }

    /**
     * Returns prepaired AMQP message.
     *
     * @param string|array|object $message
     * @param array $properties
     * @return AMQPMessage
     * @throws Exception If message is empty.
     */
    public function prepareMessage($message, $properties = null) {
        if ($message instanceof AMQPMessage) {
            return $message;
        }

        if (empty($message)) {
            throw new Exception('AMQP message can not be empty');
        }

        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }

        return new AMQPMessage($message, $properties);
    }

}
