<?php
namespace common\components\Redis;

use InvalidArgumentException;
use Yii;

/**
 * @deprecated
 */
class Key {

    private $key;

    public function __construct(...$key) {
        if (empty($key)) {
            throw new InvalidArgumentException('You must specify at least one key.');
        }

        $this->key = $this->buildKey($key);
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getValue() {
        return Yii::$app->redis->get($this->key);
    }

    public function setValue($value): self {
        Yii::$app->redis->set($this->key, $value);
        return $this;
    }

    public function delete(): self {
        Yii::$app->redis->del($this->getKey());
        return $this;
    }

    public function exists(): bool {
        return (bool)Yii::$app->redis->exists($this->key);
    }

    public function expire(int $ttl): self {
        Yii::$app->redis->expire($this->key, $ttl);
        return $this;
    }

    public function expireAt(int $unixTimestamp): self {
        Yii::$app->redis->expireat($this->key, $unixTimestamp);
        return $this;
    }

    private function buildKey(array $parts): string {
        $keyParts = [];
        foreach ($parts as $part) {
            $keyParts[] = str_replace('_', ':', $part);
        }

        return implode(':', $keyParts);
    }

}
