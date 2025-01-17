<?php
declare(strict_types=1);

namespace common\components\Authentication\Exceptions;

use Exception;
use Throwable;

final class InvalidPasswordException extends Exception implements AuthenticationException {

    public function __construct(?Throwable $previous = null) {
        parent::__construct("The entered password doesn't match the account's password", previous: $previous);
    }

}
