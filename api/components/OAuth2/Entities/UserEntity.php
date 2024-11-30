<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface {
    use EntityTrait;

    public function __construct(int $id) {
        $this->identifier = (string)$id;
    }

}
