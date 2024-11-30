<?php
declare(strict_types=1);

namespace api\components\OAuth2\Keys;

use League\OAuth2\Server\CryptKeyInterface;

class EmptyKey implements CryptKeyInterface {

    public function getKeyPath(): string {
        return '';
    }

    public function getPassPhrase(): ?string {
        return null;
    }

    public function getKeyContents(): string {
        return '';
    }

}
