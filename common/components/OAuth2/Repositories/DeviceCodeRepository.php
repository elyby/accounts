<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\DeviceCodeEntity;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

final class DeviceCodeRepository implements DeviceCodeRepositoryInterface {

    public function getNewDeviceCode(): DeviceCodeEntityInterface {
        return new DeviceCodeEntity();
    }

    public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void {
        // TODO: Implement persistDeviceCode() method.
    }

    public function getDeviceCodeEntityByDeviceCode(string $deviceCodeEntity): ?DeviceCodeEntityInterface {
        // TODO: Implement getDeviceCodeEntityByDeviceCode() method.
    }

    public function revokeDeviceCode(string $codeId): void {
        // TODO: Implement revokeDeviceCode() method.
    }

    public function isDeviceCodeRevoked(string $codeId): bool {
        // TODO: Implement isDeviceCodeRevoked() method.
    }

}
