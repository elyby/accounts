<?php
declare(strict_types=1);

namespace api\components\OAuth2\ResponseTypes;

use api\components\OAuth2\CryptTrait;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;

class BearerTokenResponse extends BaseBearerTokenResponse {
    use CryptTrait;

}
