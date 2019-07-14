<?php
declare(strict_types=1);

namespace api\aop\annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("METHOD")
 */
class CollectModelMetrics {

    /**
     * @Required()
     * @var string sets the prefix for collected metrics. Should be specified without trailing dots
     */
    public $prefix = '';

}
