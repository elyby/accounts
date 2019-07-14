<?php
namespace common\behaviors;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;

class DataBehavior extends Behavior {

    /**
     * @var string attribute name to which this behavior will be applied
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
     * @throws \yii\base\ErrorException Yii2 will catch Notice from the wrong deserialization and turn it
     * into its own Exception, so that the program can continue to work normally (you still should catch an Exception)
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
