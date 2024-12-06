<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

final class AuthCodeRepository implements AuthCodeRepositoryInterface {

    public function getNewAuthCode(): AuthCodeEntityInterface {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void {
    }

    public function revokeAuthCode(string $codeId): void {
    }

    public function isAuthCodeRevoked(string $codeId): bool {
        return false;
    }

}
