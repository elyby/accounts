<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\ScopeEntity;
use api\rbac\Permissions as P;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Webmozart\Assert\Assert;

class InternalScopeRepository implements ScopeRepositoryInterface {

    private const ALLOWED_SCOPES = [
        P::CHANGE_ACCOUNT_USERNAME,
        P::CHANGE_ACCOUNT_PASSWORD,
        P::BLOCK_ACCOUNT,
        P::OBTAIN_EXTENDED_ACCOUNT_INFO,
        P::ESCAPE_IDENTITY_VERIFICATION,
    ];

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        if (!in_array($identifier, self::ALLOWED_SCOPES, true)) {
            return null;
        }

        return new ScopeEntity($identifier);
    }

    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $client,
        $userIdentifier = null
    ): array {
        /** @var ClientEntity $client */
        Assert::isInstanceOf($client, ClientEntity::class);

        if (empty($scopes)) {
            return $scopes;
        }

        // Right now we have no available scopes for the client_credentials grant
        if (!$client->isTrusted()) {
            throw OAuthServerException::invalidScope($scopes[0]->getIdentifier());
        }

        return $scopes;
    }

}
