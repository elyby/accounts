<?php
namespace api\filters;

use Yii;
use yii\base\ActionFilter;

class NginxCache extends ActionFilter {

    /**
     * @var array|callable массив или callback, содержащий пары роут -> сколько кэшировать.
     *
     * Период можно задавать 2-умя путями:
     * - если значение начинается с префикса @, оно задаёт абсолютное время в unix timestamp,
     *   до которого ответ может быть закэширован.
     * - в ином случае значение интерпретируется как количество секунд, на которое необходимо
     *   закэшировать ответ
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
