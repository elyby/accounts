<?php
declare(strict_types=1);

namespace common\tests\helpers;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

trait ExtendedPHPMock {
    use PHPMock {
        getFunctionMock as private getOriginalFunctionMock;
        defineFunctionMock as private defineOriginalFunctionMock;
    }

    public function getFunctionMock($namespace, $name): MockObject {
        return $this->getOriginalFunctionMock(static::getClassNamespace($namespace), $name);
    }

    public static function defineFunctionMock($namespace, $name) {
        static::defineOriginalFunctionMock(static::getClassNamespace($namespace), $name);
    }

    private static function getClassNamespace(string $className): string {
        return (new ReflectionClass($className))->getNamespaceName();
    }

}
