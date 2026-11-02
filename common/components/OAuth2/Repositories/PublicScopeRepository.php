<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\ScopeEntity;
use common\components\OAuth2\ScopesMapper;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

final class PublicScopeRepository implements ScopeRepositoryInterface {

    public const string OFFLINE_ACCESS = 'offline_access';
    public const string CHANGE_SKIN = 'change_skin';

    public const string ACCOUNT_INFO = 'account_info';
    public const string ACCOUNT_EMAIL = 'account_email';
    public const string MINECRAFT_SERVER_SESSION = 'minecraft_server_session';

    private const array ALLOWED_SCOPES = [
        self::ACCOUNT_INFO,
        self::ACCOUNT_EMAIL,
        self::MINECRAFT_SERVER_SESSION,
        self::OFFLINE_ACCESS,
        self::CHANGE_SKIN,
    ];

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        // The RBAC permissions names were accepted previously, so normalize them to keep the compatibility
        $identifier = ScopesMapper::toScope($identifier);
        if (!in_array($identifier, self::ALLOWED_SCOPES, true)) {
            return null;
        }

        return new ScopeEntity($identifier);
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
