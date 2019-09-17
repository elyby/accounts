<?php
namespace common\components\Redis;

use ArrayIterator;
use IteratorAggregate;
use Yii;

/**
 * @deprecated
 */
class Set extends Key implements IteratorAggregate {

    public function add($value): self {
        Yii::$app->redis->sadd($this->getKey(), $value);
        return $this;
    }

    public function remove($value): self {
        Yii::$app->redis->srem($this->getKey(), $value);
        return $this;
    }

    public function members(): array {
        return Yii::$app->redis->smembers($this->getKey());
    }

    public function getValue(): array {
        return $this->members();
    }

    public function exists(string $value = null): bool {
        if ($value === null) {
            return parent::exists();
        }

        return (bool)Yii::$app->redis->sismember($this->getKey(), $value);
    }

    public function diff(array $sets): array {
        return Yii::$app->redis->sdiff([$this->getKey(), implode(' ', $sets)]);
    }

    /**
     * @inheritdoc
     */
    public function getIterator() {
        return new ArrayIterator($this->members());
    }

}
