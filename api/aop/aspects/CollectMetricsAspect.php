<?php
namespace api\aop\aspects;

use api\aop\annotations\CollectModelMetrics;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use Yii;

class CollectMetricsAspect implements Aspect {

    /**
     * @param MethodInvocation $invocation Invocation
     * @Around("@execution(api\aop\annotations\CollectModelMetrics)")
     */
    public function sendMetrics(MethodInvocation $invocation) {
        /** @var CollectModelMetrics $annotation */
        $annotation = $invocation->getMethod()->getAnnotation(CollectModelMetrics::class);
        $prefix = trim($annotation->prefix, '.');

        Yii::$app->statsd->inc($prefix . '.attempt');
        $result = $invocation->proceed();
        if ($result !== false) {
            Yii::$app->statsd->inc($prefix . '.success');
            return $result;
        }

        /** @var \yii\base\Model $model */
        $model = $invocation->getThis();
        $errors = array_values($model->getFirstErrors());
        if (!isset($errors[0])) {
            Yii::error('Unsuccess result with empty errors list');
            return false;
        }

        Yii::$app->statsd->inc($prefix . '.' . $errors[0]);

        return false;
    }

}
