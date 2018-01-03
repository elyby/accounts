<?php
namespace api\aop;

use api\aop\aspects;
use Doctrine\Common\Annotations\AnnotationReader;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel as BaseAspectKernel;

class AspectKernel extends BaseAspectKernel {

    protected function configureAop(AspectContainer $container): void {
        AnnotationReader::addGlobalIgnoredName('url');

        $container->registerAspect(new aspects\MockDataAspect());
        $container->registerAspect(new aspects\CollectMetricsAspect());
    }

}
