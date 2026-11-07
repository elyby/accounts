<?php
declare(strict_types=1);

namespace api\components\Tokens;

final class TokenRevokedException extends InvalidTokenException {

    public function __construct() {
        parent::__construct('The token has been revoked');
    }

}
