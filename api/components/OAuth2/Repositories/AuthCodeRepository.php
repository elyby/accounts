<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface {

    public function getNewAuthCode(): AuthCodeEntityInterface {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void {
    }

    public function revokeAuthCode($codeId): void {
    }

    public function isAuthCodeRevoked($codeId): bool {
        return false;
    }

}
