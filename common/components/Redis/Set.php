<?php
namespace common\components\Redis;

use ArrayIterator;
use IteratorAggregate;

class Set extends Key implements IteratorAggregate {

    public function add($value): self {
        $this->getRedis()->sadd($this->getKey(), $value);
        return $this;
    }

    public function remove($value): self {
        $this->getRedis()->srem($this->getKey(), $value);
        return $this;
    }

    public function members(): array {
        return $this->getRedis()->smembers($this->getKey());
    }

    public function getValue(): array {
        return $this->members();
    }

    public function exists(string $value = null): bool {
        if ($value === null) {
            return parent::exists();
        }

        return (bool)$this->getRedis()->sismember($this->getKey(), $value);
    }

    public function diff(array $sets): array {
        return $this->getRedis()->sdiff([$this->getKey(), implode(' ', $sets)]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new ArrayIterator($this->members());
    }

}
