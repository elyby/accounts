<?php
declare(strict_types=1);

namespace common\emails\exceptions;

use Exception;
use Throwable;

class CannotRenderEmailException extends Exception {

    public function __construct(Throwable $previous = null) {
        parent::__construct('Unable to render a template', 0, $previous);
    }

}
