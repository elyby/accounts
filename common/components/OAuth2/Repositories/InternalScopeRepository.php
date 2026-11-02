<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use api\rbac\Permissions as P;
use common\components\OAuth2\Entities\ClientEntity;
use common\components\OAuth2\Entities\ScopeEntity;
use common\components\OAuth2\ScopesMapper;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

final class InternalScopeRepository implements ScopeRepositoryInterface {

    public const string INTERNAL_ACCOUNT_INFO = 'internal_account_info';

    private const array ALLOWED_SCOPES = [
        P::CHANGE_ACCOUNT_USERNAME,
        P::CHANGE_ACCOUNT_PASSWORD,
        P::BLOCK_ACCOUNT,
        self::INTERNAL_ACCOUNT_INFO,
        P::ESCAPE_IDENTITY_VERIFICATION,
    ];

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        // The RBAC permissions names were accepted previously, so normalize them to keep the compatibility
        $identifier = ScopesMapper::toScope($identifier);
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
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        if (empty($scopes)) {
            return $scopes;
        }

        /** @var ClientEntity $clientEntity */
        // Right now we have no available scopes for the client_credentials grant
        if (!$clientEntity->isTrusted()) {
            throw OAuthServerException::invalidScope($scopes[0]->getIdentifier());
        }

        return $scopes;
    }

}
