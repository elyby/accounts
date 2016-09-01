<?php
namespace api\modules\authserver\exceptions;

class ForbiddenOperationException extends AuthserverException {

    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($status = 401, $message, $code, $previous);
    }

}
