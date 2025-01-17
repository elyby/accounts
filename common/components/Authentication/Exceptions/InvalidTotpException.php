<?php
declare(strict_types=1);

namespace common\components\Authentication\Exceptions;

use Exception;
use Throwable;

final class InvalidTotpException extends Exception implements AuthenticationException {

    public function __construct(?Throwable $previous = null) {
        parent::__construct('Incorrect TOTP value', previous: $previous);
    }

}
