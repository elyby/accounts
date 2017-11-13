<?php
namespace api\aop;

use api\aop\aspects;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel as BaseAspectKernel;

class AspectKernel extends BaseAspectKernel {

    protected function configureAop(AspectContainer $container): void {
    }

}
