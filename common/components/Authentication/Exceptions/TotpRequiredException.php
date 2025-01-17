<?php
declare(strict_types=1);

namespace common\components\Authentication\Exceptions;

use Exception;
use Throwable;

final class TotpRequiredException extends Exception implements AuthenticationException {

    public function __construct(?Throwable $previous = null) {
        parent::__construct('Two-factor authentication is enabled for the account and you need to pass the TOTP', previous: $previous);
    }

}
