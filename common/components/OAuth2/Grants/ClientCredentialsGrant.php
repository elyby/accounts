<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\CryptTrait;
use League\OAuth2\Server\Grant\ClientCredentialsGrant as BaseClientCredentialsGrant;

final class ClientCredentialsGrant extends BaseClientCredentialsGrant {
    use CryptTrait;

}
