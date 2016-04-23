<?php
namespace console\controllers;

use common\components\Mojang\Api as MojangApi;
use common\components\Mojang\exceptions\NoContentException;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use console\controllers\base\AmqpController;
use Yii;

class AccountQueueController extends AmqpController {

    public function getExchangeName() {
        return 'account';
    }

    public function getQueueName() {
        return 'account-operations';
    }

    public function getExchangeDeclareArgs() {
        return array_replace(parent::getExchangeDeclareArgs(), [
            3 => true, // durable -> true
        ]);
    }

    public function routeUsernameChanged(UsernameChanged $body) {
        $mojangApi = new MojangApi();
        try {
            $response = $mojangApi->usernameToUUID($body->newUsername);
        } catch (NoContentException $e) {
            $response = false;
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

}
