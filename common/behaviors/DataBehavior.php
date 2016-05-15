<?php
namespace common\behaviors;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;

class DataBehavior extends Behavior {
    /**
     * @var string имя атрибута, к которому будет применяться поведение
     */
    public $attribute = '_data';

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function setKey(string $key, $value) {
        $data = $this->getData();
        $data[$key] = $value;
        $this->owner->{$this->attribute} = serialize($data);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getKey(string $key) {
        return ArrayHelper::getValue($this->getData(), $key);
    }

    /**
     * @return array
     * @throws \yii\base\ErrorException Yii2 подхватит Notice от неправильной десериализаци и превратит его
     * в свой Exception, благодаря чему программа сможем продолжить нормально работать (вернее ловить Exception)
     */
    private function getData() {
        $data = $this->owner->{$this->attribute};
        if (is_string($data)) {
            $data = unserialize($data);
        } else {
            $data = [];
        }

        return $data;
    }

}
