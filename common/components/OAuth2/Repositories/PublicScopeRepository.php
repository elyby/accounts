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

    /**
     * @see https://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
     */
    public const string OPENID = 'openid';
    public const string PROFILE = 'profile';
    public const string EMAIL = 'email';

    public const array ALLOWED_SCOPES = [
        self::OPENID,
        self::PROFILE,
        self::EMAIL,
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
        // Collapses the duplicating scopes, e.g. "profile" and "account_info" (see ScopesMapper::normalizeScopes())
        $identifiers = ScopesMapper::normalizeScopes(array_map(fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes));

        return array_values(array_filter($scopes, fn(ScopeEntityInterface $scope): bool => in_array($scope->getIdentifier(), $identifiers, true)));
    }

}
