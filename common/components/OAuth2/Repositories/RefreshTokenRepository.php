<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface {
        return null;
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void {
        // Do nothing
    }

    public function revokeRefreshToken(string $tokenId): void {
        // Do nothing
    }

    public function isRefreshTokenRevoked(string $tokenId): bool {
        return false;
    }

}
