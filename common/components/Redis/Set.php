<?php
namespace common\components\Redis;

use ArrayIterator;
use IteratorAggregate;
use Yii;

class Set extends Key implements IteratorAggregate {

    /**
     * @return Connection
     */
    public static function getDb() {
        return Yii::$app->redis;
    }

    public function add($value) {
        static::getDb()->sadd($this->key, $value);
        return $this;
    }

    public function remove($value) {
        static::getDb()->srem($this->key, $value);
        return $this;
    }

    public function members() {
        return static::getDb()->smembers($this->key);
    }

    public function getValue() {
        return $this->members();
    }

    public function exists($value) {
        return (bool)static::getDb()->sismember($this->key, $value);
    }

    public function diff(array $sets) {
        return static::getDb()->sdiff([$this->key, implode(' ', $sets)]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new ArrayIterator($this->members());
    }

}
