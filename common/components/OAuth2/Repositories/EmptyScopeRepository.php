<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * In our application we use separate scopes repositories for different grants.
 * To create an instance of the authorization server, you need to pass the scopes
 * repository. This class acts as a dummy to meet this requirement.
 */
final class EmptyScopeRepository implements ScopeRepositoryInterface {

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        return null;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        return $scopes;
    }

}
