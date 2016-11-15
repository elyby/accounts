<?php
namespace console\controllers;

use common\components\Mojang\Api as MojangApi;
use common\components\Mojang\exceptions\NoContentException;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use Ely\Amqp\Builder\Configurator;
use GuzzleHttp\Exception\RequestException;

class AccountQueueController extends AmqpController {

    public function getExchangeName() {
        return 'events';
    }

    public function configure(Configurator $configurator) {
        $configurator->exchange->topic()->durable();
        $configurator->queue->name('accounts-accounts-events')->durable();
        $configurator->bind->routingKey('accounts.username-changed');
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
