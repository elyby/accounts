<?php
declare(strict_types=1);
namespace common\tests\helpers;

use phpmock\mockery\PHPMockery;
use ReflectionClass;

class Mock {

    /**
     * @param string $className
     * @param string $function
     *
     * @return \Mockery\Expectation
     */
    public static function func(string $className, string $function) {
        return PHPMockery::mock(self::getClassNamespace($className), $function);
    }

    private static function getClassNamespace(string $className): string {
        return (new ReflectionClass($className))->getNamespaceName();
    }

}
