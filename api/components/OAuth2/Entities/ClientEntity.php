<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface {
    use EntityTrait;
    use ClientTrait;

    public function __construct(string $id, string $name, $redirectUri, bool $isTrusted = false) {
        $this->identifier = $id;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isConfidential = $isTrusted;
    }

}
