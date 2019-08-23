<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

class ScopeEntity implements ScopeEntityInterface {
    use EntityTrait;
    use ScopeTrait;

    public function __construct(string $id) {
        $this->identifier = $id;
    }

}
