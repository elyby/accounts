<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

final class AccessTokenRepository implements AccessTokenRepositoryInterface {

    /**
     * @inheritDoc
     * @phpstan-param non-empty-string|null $userIdentifier
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        array_map($accessToken->addScope(...), $scopes);
        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier($userIdentifier);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void {
        // We don't store access tokens, so there's no need to do anything here
    }

    public function revokeAccessToken(string $tokenId): void {
        // We don't store access tokens, so there's no need to do anything here
    }

    public function isAccessTokenRevoked(string $tokenId): bool {
        return false;
    }

}
