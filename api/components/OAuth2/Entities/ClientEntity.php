<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface {
    use EntityTrait;
    use ClientTrait;

    /**
     * @param non-empty-string $id
     * @param string|string[] $redirectUri
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
