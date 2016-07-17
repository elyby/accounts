<?php
namespace common\components\redis;

use IteratorAggregate;
use Yii;

class Set extends Key implements IteratorAggregate {

    /**
     * @return \yii\redis\Connection
     */
    public static function getDb() {
        return Yii::$app->redis;
    }

    public function add($value) {
        $this->getDb()->executeCommand('SADD', [$this->key, $value]);
        return $this;
    }

    public function remove($value) {
        $this->getDb()->executeCommand('SREM', [$this->key, $value]);
        return $this;
    }

    public function members() {
        return $this->getDb()->executeCommand('SMEMBERS', [$this->key]);
    }

    public function getValue() {
        return $this->members();
    }

    public function exists($value) {
        return !!$this->getDb()->executeCommand('SISMEMBER', [$this->key, $value]);
    }

    public function diff(array $sets) {
        return $this->getDb()->executeCommand('SDIFF', [$this->key, implode(' ', $sets)]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new \ArrayIterator($this->members());
    }

}
