<?php
declare(strict_types=1);

namespace common\tests\_support;

use ReflectionClass;

/**
 * @deprecated
 */
trait ProtectedCaller {

    protected function callProtected(object $object, string $methodName, ...$args) {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

}
