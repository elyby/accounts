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
     * @var bool
     */
    private $isTrusted;

    public function __construct(string $id, string $name, $redirectUri, bool $isTrusted) {
        $this->identifier = $id;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isTrusted = $isTrusted;
    }

    public function isConfidential(): bool {
        return true;
    }

    public function isTrusted(): bool {
        return $this->isTrusted;
    }

}
