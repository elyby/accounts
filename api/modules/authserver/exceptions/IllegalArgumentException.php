<?php
namespace api\modules\authserver\exceptions;

class IllegalArgumentException extends AuthserverException {

    public function __construct($message = 'credentials can not be null.') {
        parent::__construct(400, $message);
    }

}
