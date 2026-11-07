<?php
declare(strict_types=1);

namespace api\components\Tokens;

final class TokenExpiredException extends InvalidTokenException {

    public function __construct() {
        parent::__construct('The token is expired');
    }

}
