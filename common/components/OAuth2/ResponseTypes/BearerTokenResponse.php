<?php
declare(strict_types=1);

namespace common\components\OAuth2\ResponseTypes;

use common\components\OAuth2\CryptTrait;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;

final class BearerTokenResponse extends BaseBearerTokenResponse {
    use CryptTrait;

}
