<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\ScopeEntity;
use api\rbac\Permissions as P;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class PublicScopeRepository implements ScopeRepositoryInterface {

    public const string OFFLINE_ACCESS = 'offline_access';
    public const string CHANGE_SKIN = 'change_skin';

    private const string ACCOUNT_INFO = 'account_info';
    private const string ACCOUNT_EMAIL = 'account_email';

    private const array PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS = [
        self::ACCOUNT_INFO => P::OBTAIN_OWN_ACCOUNT_INFO,
        self::ACCOUNT_EMAIL => P::OBTAIN_ACCOUNT_EMAIL,
    ];

    private const array ALLOWED_SCOPES = [
        P::OBTAIN_OWN_ACCOUNT_INFO,
        P::OBTAIN_ACCOUNT_EMAIL,
        P::MINECRAFT_SERVER_SESSION,
        self::OFFLINE_ACCESS,
        self::CHANGE_SKIN,
    ];

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
        $identifier = $this->convertToInternalPermission($identifier);
        if (!in_array($identifier, self::ALLOWED_SCOPES, true)) {
            return null;
        }

        return new ScopeEntity($identifier);
    }

    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        return $scopes;
    }

    private function convertToInternalPermission(string $publicScope): string {
        return self::PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS[$publicScope] ?? $publicScope;
    }

}
