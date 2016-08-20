<?php
namespace api\modules\authserver\exceptions;

class IllegalArgumentException extends AuthserverException {

    public function __construct($status = null, $message = null, $code = 0, \Exception $previous = null) {
        parent::__construct(400, 'credentials can not be null.', $code, $previous);
    }

}
