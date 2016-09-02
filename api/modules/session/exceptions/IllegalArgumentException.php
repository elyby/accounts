<?php
namespace api\modules\session\exceptions;

class IllegalArgumentException extends SessionServerException {

    public function __construct($status = null, $message = null, $code = 0, \Exception $previous = null) {
        parent::__construct(400, 'credentials can not be null.', $code, $previous);
    }

}
