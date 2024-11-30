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

class InternalScopeRepository implements ScopeRepositoryInterface {

    private const array ALLOWED_SCOPES = [
        P::CHANGE_ACCOUNT_USERNAME,
        P::CHANGE_ACCOUNT_PASSWORD,
        P::BLOCK_ACCOUNT,
        P::OBTAIN_EXTENDED_ACCOUNT_INFO,
        P::ESCAPE_IDENTITY_VERIFICATION,
    ];

    private const array PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS = [
        'internal_account_info' => P::OBTAIN_EXTENDED_ACCOUNT_INFO,
    ];

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        $identifier = $this->convertToInternalPermission($identifier);
        if (!in_array($identifier, self::ALLOWED_SCOPES, true)) {
            return null;
        }

        return new ScopeEntity($identifier);
    }

    /**
     * @throws OAuthServerException
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        /** @var ClientEntity $clientEntity */

        if (empty($scopes)) {
            return $scopes;
        }

        // Right now we have no available scopes for the client_credentials grant
        if (!$clientEntity->isTrusted()) {
            throw OAuthServerException::invalidScope($scopes[0]->getIdentifier());
        }

        return $scopes;
    }

    private function convertToInternalPermission(string $publicScope): string {
        return self::PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS[$publicScope] ?? $publicScope;
    }

}
