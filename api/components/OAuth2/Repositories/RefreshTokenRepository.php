<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface {
        return null;
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void {
        // Do nothing
    }

    public function revokeRefreshToken($tokenId): void {
        // Do nothing
    }

    public function isRefreshTokenRevoked($tokenId): bool {
        return false;
    }

}
