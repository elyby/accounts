<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\ScopeEntity;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface {

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        // TODO: validate not exists scopes
        return new ScopeEntity($identifier);
    }

    /**
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid and optionally
     * append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface $scopes
     * @param string $grantType
     * @param \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity
     * @param null|string $userIdentifier
     *
     * @return ScopeEntityInterface
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ): array {
        // TODO: Implement finalizeScopes() method.
    }

}
