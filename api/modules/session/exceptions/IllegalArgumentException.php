<?php
namespace api\modules\session\exceptions;

class IllegalArgumentException extends SessionServerException {

    public function __construct($message = 'credentials can not be null.', $code = 0, \Exception $previous = null) {
        parent::__construct(400, $message, $code, $previous);
    }

}
