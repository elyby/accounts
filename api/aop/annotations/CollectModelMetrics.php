<?php
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
     * @var string задаёт префикс для отправки метрик. Задаётся без ведущей и без завершающей точки.
     */
    public $prefix = '';

}
