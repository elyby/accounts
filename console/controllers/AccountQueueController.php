<?php
namespace console\controllers;

use common\components\Mojang\Api as MojangApi;
use common\components\Mojang\exceptions\NoContentException;
use common\components\RabbitMQ\Component as RabbitMQComponent;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use console\controllers\base\AmqpController;
use GuzzleHttp\Exception\RequestException;

class AccountQueueController extends AmqpController {

    public function getExchangeName() {
        return 'events';
    }

    public function getQueueName() {
        return 'accounts-events';
    }

    protected function getExchangeDeclareArgs() {
        return array_replace(parent::getExchangeDeclareArgs(), [
            1 => RabbitMQComponent::TYPE_TOPIC, // type -> topic
            3 => true, // durable -> true
        ]);
    }

    protected function getQueueBindArgs($exchangeName, $queueName) {
        return [$queueName, $exchangeName, 'accounts.#'];
    }

    public function getRoutesMap() {
        return [
            'accounts.username-changed' => 'routeUsernameChanged',
        ];
    }

    public function routeUsernameChanged(UsernameChanged $body) {
        $mojangApi = $this->createMojangApi();
        try {
            $response = $mojangApi->usernameToUUID($body->newUsername);
        } catch (NoContentException $e) {
            $response = false;
        } catch (RequestException $e) {
            return true;
        }

        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($body->newUsername);
        if ($response === false) {
            if ($mojangUsername !== null) {
                $mojangUsername->delete();
            }
        } else {
            if ($mojangUsername === null) {
                $mojangUsername = new MojangUsername();
                $mojangUsername->username = $response->name;
                $mojangUsername->uuid = $response->id;
            } else {
                $mojangUsername->uuid = $response->id;
                $mojangUsername->touch('last_pulled_at');
            }

            $mojangUsername->save();
        }

        return true;
    }

    /**
     * @return MojangApi
     */
    protected function createMojangApi() : MojangApi {
        return new MojangApi();
    }

}
