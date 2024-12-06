<?php
declare(strict_types=1);

namespace common\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

final class ScopeEntity implements ScopeEntityInterface {
    use EntityTrait;
    use ScopeTrait;

    /**
     * @phpstan-param non-empty-string $id
     */
    public function __construct(string $id) {
        $this->identifier = $id;
    }

}
