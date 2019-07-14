<?php
namespace api\modules\authserver\exceptions;

use ReflectionClass;
use yii\web\HttpException;

class AuthserverException extends HttpException {

    /**
     * Reflection is faster, weird as it may seem:
     * @url https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace#comment_19313
     *
     * @return string
     */
    public function getName() {
        return (new ReflectionClass($this))->getShortName();
    }

}
