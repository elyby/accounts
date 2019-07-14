<?php
declare(strict_types=1);

namespace api\filters;

use Yii;
use yii\base\ActionFilter;

class NginxCache extends ActionFilter {

    /**
     * @var array|callable array or callback, contains pairs of route => cache duration.
     *
     * Duration can be set in 2-ways:
     * - if the value starts with the @ prefix, it sets the absolute time
     *   in unix timestamp that the response can be cached to.
     * - otherwise, the value is interpreted as the number of seconds
     *   for which the response must be cached
     */
    public $rules;

    public function afterAction($action, $result) {
        $rule = $this->rules[$action->id] ?? null;
        if ($rule !== null) {
            if (is_callable($rule)) {
                $cacheTime = $rule($action);
            } else {
                $cacheTime = $rule;
            }

            Yii::$app->response->headers->set('X-Accel-Expires', $cacheTime);
        }

        return parent::afterAction($action, $result);
    }

}
