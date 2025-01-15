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

        if (!in_array($client->type, [OauthClient::TYPE_WEB_APPLICATION, OauthClient::TYPE_DESKTOP_APPLICATION], true)) {
            return false;
        }

        if ($client->type === OauthClient::TYPE_WEB_APPLICATION && !empty($clientSecret) && $clientSecret !== $client->secret) {
            return false;
        }

        return true;
    }

    private function findModel(string $id): ?OauthClient {
        return OauthClient::findOne(['id' => $id]);
    }

}
