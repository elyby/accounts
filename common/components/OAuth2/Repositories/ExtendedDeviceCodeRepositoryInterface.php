<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

interface ExtendedDeviceCodeRepositoryInterface extends DeviceCodeRepositoryInterface {

    /**
     * @phpstan-param non-empty-string $userCode
     */
    public function getDeviceCodeEntityByUserCode(string $userCode): ?DeviceCodeEntityInterface;

}
