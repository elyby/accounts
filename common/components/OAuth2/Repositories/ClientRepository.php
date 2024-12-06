<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\ClientEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

final class ClientRepository implements ClientRepositoryInterface {

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface {
        $client = $this->findModel($clientIdentifier);
        if ($client === null) {
            return null;
        }

        return ClientEntity::fromModel($client);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool {
        $client = $this->findModel($clientIdentifier);
        if ($client === null) {
            return false;
        }

        if ($client->type !== OauthClient::TYPE_APPLICATION) {
            return false;
        }

        if (!empty($clientSecret) && $clientSecret !== $client->secret) {
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
