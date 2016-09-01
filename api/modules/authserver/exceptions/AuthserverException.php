<?php
namespace api\modules\authserver\exceptions;

use ReflectionClass;
use yii\web\HttpException;

class AuthserverException extends HttpException {

    /**
     * Рефлексия быстрее, как ни странно:
     * @url https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace#comment_19313
     *
     * @return string
     */
    public function getName() {
        return (new ReflectionClass($this))->getShortName();
    }

}
