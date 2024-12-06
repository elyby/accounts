<?php
declare(strict_types=1);

namespace common\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class ClientEntity implements ClientEntityInterface {
    use EntityTrait;
    use ClientTrait;

    /**
     * @phpstan-param non-empty-string $id
     * @phpstan-param string|list<string> $redirectUri
     */
    public function __construct(
        string $id,
        string $name,
        string|array $redirectUri,
        private readonly bool $isTrusted,
    ) {
        $this->identifier = $id;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
    }

    public function isConfidential(): bool {
        return true;
    }

    public function isTrusted(): bool {
        return $this->isTrusted;
    }

}
