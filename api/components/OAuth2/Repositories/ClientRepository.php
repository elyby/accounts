<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\ClientEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface {

    public function getClientEntity($clientId): ?ClientEntityInterface {
        $client = $this->findModel($clientId);
        if ($client === null) {
            return null;
        }

        return new ClientEntity($client->id, $client->name, $client->redirect_uri ?? '', (bool)$client->is_trusted);
    }

    public function validateClient($clientId, $clientSecret, $grantType): bool {
        $client = $this->findModel($clientId);
        if ($client === null) {
            return false;
        }

        if ($client->type !== OauthClient::TYPE_APPLICATION) {
            return false;
        }

        if ($clientSecret !== null && $clientSecret !== $client->secret) {
            return false;
        }

        return true;
    }

    private function findModel(string $id): ?OauthClient {
        $client = OauthClient::findOne(['id' => $id]);
        if ($client === null || $client->type !== OauthClient::TYPE_APPLICATION) {
            return null;
        }

        return $client;
    }

}
