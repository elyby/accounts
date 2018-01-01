<?php
namespace console\controllers;

use common\components\Mojang\Api as MojangApi;
use common\components\Mojang\exceptions\NoContentException;
use common\models\Account;
use common\models\amqp\AccountBanned;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use Ely\Amqp\Builder\Configurator;
use GuzzleHttp\Exception\RequestException;
use Yii;

class AccountQueueController extends AmqpController {

    public function getExchangeName() {
        return 'events';
    }

    public function configure(Configurator $configurator) {
        $configurator->exchange->topic()->durable();
        $configurator->queue->name('accounts-accounts-events')->durable();
        $configurator->bind->routingKey('accounts.username-changed')
            ->add()->routingKey('account.account-banned');
    }

    public function getRoutesMap() {
        return [
            'accounts.username-changed' => 'routeUsernameChanged',
            'accounts.account-banned' => 'routeAccountBanned',
        ];
    }

    public function routeUsernameChanged(UsernameChanged $body): bool {
        Yii::$app->statsd->inc('worker.account.usernameChanged.attempt');
        $mojangApi = $this->createMojangApi();
        try {
            $response = $mojangApi->usernameToUUID($body->newUsername);
            Yii::$app->statsd->inc('worker.account.usernameChanged.found');
        } catch (NoContentException $e) {
            $response = false;
            Yii::$app->statsd->inc('worker.account.usernameChanged.not_found');
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

    public function routeAccountBanned(AccountBanned $body): bool {
        $account = Account::findOne($body->accountId);
        if ($account === null) {
            Yii::warning('Cannot find banned account ' . $body->accountId . '. Skipping.');
            return true;
        }

        foreach ($account->sessions as $authSession) {
            $authSession->delete();
        }

        foreach ($account->minecraftAccessKeys as $key) {
            $key->delete();
        }

        foreach ($account->oauthSessions as $oauthSession) {
            $oauthSession->delete();
        }

        return true;
    }

    /**
     * @return MojangApi
     */
    protected function createMojangApi(): MojangApi {
        return new MojangApi();
    }

}
