<?php
declare(strict_types=1);

namespace common\components\OAuth2;

use api\rbac\Permissions as P;
use common\components\OAuth2\Repositories\InternalScopeRepository;
use common\components\OAuth2\Repositories\PublicScopeRepository;

/**
 * OAuth2 tokens and sessions store the public scopes names, which are requested by the clients.
 * Some of them have different names in our RBAC, so they must be converted when the permissions are checked.
 *
 * Previously, the RBAC permissions names were stored in the tokens and sessions,
 * so this class also allows to normalize them back to the public scopes names.
 */
final class ScopesMapper {

    private const array PUBLIC_SCOPES_TO_PERMISSIONS = [
        PublicScopeRepository::ACCOUNT_INFO => P::OBTAIN_OWN_ACCOUNT_INFO,
        PublicScopeRepository::ACCOUNT_EMAIL => P::OBTAIN_ACCOUNT_EMAIL,
        PublicScopeRepository::MINECRAFT_SERVER_SESSION => P::MINECRAFT_SERVER_SESSION,
        InternalScopeRepository::INTERNAL_ACCOUNT_INFO => P::OBTAIN_EXTENDED_ACCOUNT_INFO,
    ];

    /**
     * Converts the public scope name to the RBAC permission name. Unknown values are returned as is.
     */
    public static function toPermission(string $scope): string {
        return self::PUBLIC_SCOPES_TO_PERMISSIONS[$scope] ?? $scope;
    }

    /**
     * Converts the RBAC permission name to the public scope name. Unknown values are returned as is.
     *
     * @return ($permission is non-empty-string ? non-empty-string : string)
     */
    public static function toScope(string $permission): string {
        return array_flip(self::PUBLIC_SCOPES_TO_PERMISSIONS)[$permission] ?? $permission;
    }

    /**
     * @param iterable<non-empty-string> $scopes
     * @return list<non-empty-string>
     */
    public static function normalizeScopes(iterable $scopes): array {
        $result = [];
        foreach ($scopes as $scope) {
            $result[] = self::toScope($scope);
        }

        return array_values(array_unique($result));
    }

}
