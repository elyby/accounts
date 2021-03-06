<?php
declare(strict_types=1);

namespace common\emails\exceptions;

use Exception;
use Throwable;

class CannotSendEmailException extends Exception {

    public function __construct(Throwable $previous = null) {
        parent::__construct('Unable send email', 0, $previous);
    }

}
