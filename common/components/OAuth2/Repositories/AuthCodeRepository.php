<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Yii;

/**
 * Auth codes are self-contained encrypted values, so there is no need to store them.
 * But the code must be used only once (RFC 6749 section 4.1.2), so the used codes ids are stored
 * until the code is expired.
 */
final class AuthCodeRepository implements AuthCodeRepositoryInterface {

    /**
     * Must be greater than the auth code TTL
     */
    private const int USED_CODE_TTL = 60 * 60;

    public function getNewAuthCode(): AuthCodeEntityInterface {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void {
    }

    public function revokeAuthCode(string $codeId): void {
        Yii::$app->redis->setex($this->buildKey($codeId), self::USED_CODE_TTL, '1');
    }

    /**
     * The library checks the code at the beginning of the exchange and revokes it only at the end.
     * To prevent the concurrent exchange of the same code, the code is atomically marked as used during the check.
     */
    public function isAuthCodeRevoked(string $codeId): bool {
        return Yii::$app->redis->set($this->buildKey($codeId), '1', 'EX', self::USED_CODE_TTL, 'NX') !== true;
    }

    private function buildKey(string $codeId): string {
        return "oauth:auth-codes:used:{$codeId}";
    }

}
