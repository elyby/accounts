<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use api\components\Tokens\TokensFactory;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface {
    use EntityTrait;
    use TokenEntityTrait {
        getExpiryDateTime as parentGetExpiryDateTime;
    }

    public function __toString(): string {
        // TODO: strip "offline_access" scope from the scopes list
        return (string)TokensFactory::createForOAuthClient($this);
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void {
        // We use a general-purpose component to build JWT tokens, so there is no need to keep the key
    }

    public function getExpiryDateTime() {
        // TODO: extend token life depending on scopes list
        return $this->parentGetExpiryDateTime();
    }

}
