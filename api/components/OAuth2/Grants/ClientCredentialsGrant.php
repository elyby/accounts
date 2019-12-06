<?php
declare(strict_types=1);

namespace api\components\OAuth2\Grants;

use api\components\OAuth2\CryptTrait;
use League\OAuth2\Server\Grant\ClientCredentialsGrant as BaseClientCredentialsGrant;

class ClientCredentialsGrant extends BaseClientCredentialsGrant {
    use CryptTrait;

}
