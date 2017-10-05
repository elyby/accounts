<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\ScopeEntity;
use Assert\Assert;
use common\rbac\Permissions as P;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeStorage extends AbstractStorage implements ScopeInterface {

    public const OFFLINE_ACCESS = 'offline_access';

    private const PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS = [
        'account_info' => P::OBTAIN_OWN_ACCOUNT_INFO,
        'account_email' => P::OBTAIN_ACCOUNT_EMAIL,
        'account_block' => P::BLOCK_ACCOUNT,
        'internal_account_info' => P::OBTAIN_EXTENDED_ACCOUNT_INFO,
    ];

    private const AUTHORIZATION_CODE_PERMISSIONS = [
        P::OBTAIN_OWN_ACCOUNT_INFO,
        P::OBTAIN_ACCOUNT_EMAIL,
        P::MINECRAFT_SERVER_SESSION,
        self::OFFLINE_ACCESS,
    ];

    private const CLIENT_CREDENTIALS_PERMISSIONS = [
    ];

    private const CLIENT_CREDENTIALS_PERMISSIONS_INTERNAL = [
        P::CHANGE_ACCOUNT_USERNAME,
        P::CHANGE_ACCOUNT_PASSWORD,
        P::BLOCK_ACCOUNT,
        P::OBTAIN_EXTENDED_ACCOUNT_INFO,
        P::ESCAPE_IDENTITY_VERIFICATION,
    ];

    /**
     * @param string $scope
     * @param string $grantType передаётся, если запрос поступает из grant. В этом случае нужно отфильтровать
     *                          только те права, которые можно получить на этом grant.
     * @param string $clientId
     *
     * @return ScopeEntity|null
     */
    public function get($scope, $grantType = null, $clientId = null): ?ScopeEntity {
        $permission = $this->convertToInternalPermission($scope);

        if ($grantType === 'authorization_code') {
            $permissions = self::AUTHORIZATION_CODE_PERMISSIONS;
        } elseif ($grantType === 'client_credentials') {
            $permissions = self::CLIENT_CREDENTIALS_PERMISSIONS;
            $isTrusted = false;
            if ($clientId !== null) {
                /** @var ClientEntity $client */
                $client = $this->server->getClientStorage()->get($clientId);
                Assert::that($client)->isInstanceOf(ClientEntity::class);

                /** @noinspection NullPointerExceptionInspection */
                $isTrusted = $client->isTrusted();
            }

            if ($isTrusted) {
                $permissions = array_merge($permissions, self::CLIENT_CREDENTIALS_PERMISSIONS_INTERNAL);
            }
        } else {
            $permissions = array_merge(
                self::AUTHORIZATION_CODE_PERMISSIONS,
                self::CLIENT_CREDENTIALS_PERMISSIONS,
                self::CLIENT_CREDENTIALS_PERMISSIONS_INTERNAL
            );
        }

        if (!in_array($permission, $permissions, true)) {
            return null;
        }

        $entity = new ScopeEntity($this->server);
        $entity->setId($permission);

        return $entity;
    }

    private function convertToInternalPermission(string $publicScope): string {
        return self::PUBLIC_SCOPES_TO_INTERNAL_PERMISSIONS[$publicScope] ?? $publicScope;
    }

}
