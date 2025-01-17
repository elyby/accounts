<?php
declare(strict_types=1);

namespace common\components\Authentication\Exceptions;

use Exception;
use Throwable;

final class UnknownLoginException extends Exception implements AuthenticationException {

    public function __construct(?Throwable $previous = null) {
        parent::__construct('The account with the specified login does not exist', previous: $previous);
    }

}
