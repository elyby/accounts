<?php
namespace common\components\Redis;

use ArrayIterator;
use IteratorAggregate;

class Set extends Key implements IteratorAggregate {

    public function add($value) {
        $this->getRedis()->sadd($this->key, $value);
        return $this;
    }

    public function remove($value) {
        $this->getRedis()->srem($this->key, $value);
        return $this;
    }

    public function members() {
        return $this->getRedis()->smembers($this->key);
    }

    public function getValue() {
        return $this->members();
    }

    public function exists(string $value = null) : bool {
        if ($value === null) {
            return parent::exists();
        } else {
            return (bool)$this->getRedis()->sismember($this->key, $value);
        }
    }

    public function diff(array $sets) {
        return $this->getRedis()->sdiff([$this->key, implode(' ', $sets)]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new ArrayIterator($this->members());
    }

}
