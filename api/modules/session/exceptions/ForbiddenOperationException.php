<?php
namespace api\modules\session\exceptions;

class ForbiddenOperationException extends SessionServerException {

    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($status = 401, $message, $code, $previous);
    }

}
