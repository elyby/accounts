<?php
declare(strict_types=1);

namespace common\tests\helpers;

use phpmock\phpunit\MockObjectProxy;
use phpmock\phpunit\PHPMock;
use ReflectionClass;

trait ExtendedPHPMock {
    use PHPMock {
        getFunctionMock as private getOriginalFunctionMock;
        defineFunctionMock as private defineOriginalFunctionMock;
    }

    public function getFunctionMock($namespace, $name): MockObjectProxy {
        // @phpstan-ignore return.type
        return $this->getOriginalFunctionMock(self::getClassNamespace($namespace), $name);
    }

    public static function defineFunctionMock($namespace, $name): void {
        self::defineOriginalFunctionMock(self::getClassNamespace($namespace), $name);
    }

    private static function getClassNamespace(string $className): string {
        return (new ReflectionClass($className))->getNamespaceName();
    }

}
