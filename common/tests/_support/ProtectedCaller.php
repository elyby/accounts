<?php
namespace common\tests\_support;

use ReflectionClass;

trait ProtectedCaller {

    protected function callProtected($object, string $function, ...$args) {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($function);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

}
