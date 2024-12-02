<?php
declare(strict_types=1);

namespace common\tests\helpers;

use phpmock\Deactivatable;
use phpmock\phpunit\MockObjectProxy;
use phpmock\phpunit\PHPMock;
use ReflectionClass;

trait ExtendedPHPMock {
    use PHPMock {
        getFunctionMock as private getOriginalFunctionMock;
        defineFunctionMock as private defineOriginalFunctionMock;
    }

    /**
     * @var Deactivatable[]
     */
    private array $deactivatables = [];

    public function getFunctionMock($namespace, $name): MockObjectProxy {
        // @phpstan-ignore return.type
        return $this->getOriginalFunctionMock(self::getClassNamespace($namespace), $name);
    }

    public static function defineFunctionMock($namespace, $name): void {
        self::defineOriginalFunctionMock(self::getClassNamespace($namespace), $name);
    }

    /**
     * Override this method since original implementation relies on the PHPUnit's state,
     * but we're dealing with the Codeception, which uses different event system
     */
    public function registerForTearDown(Deactivatable $deactivatable): void {
        $this->deactivatables[] = $deactivatable;
    }

    protected function _after(): void {
        parent::_after();
        foreach ($this->deactivatables as $deactivatable) {
            $deactivatable->disable();
        }

        $this->deactivatables = [];
    }

    private static function getClassNamespace(string $className): string {
        return (new ReflectionClass($className))->getNamespaceName();
    }

}
