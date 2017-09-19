<?php
namespace common\components\Redis;

use InvalidArgumentException;
use Yii;

class Key {

    private $key;

    public function __construct(...$key) {
        if (empty($key)) {
            throw new InvalidArgumentException('You must specify at least one key.');
        }

        $this->key = $this->buildKey($key);
    }

    public function getRedis(): Connection {
        return Yii::$app->redis;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getValue() {
        return $this->getRedis()->get($this->key);
    }

    public function setValue($value): self {
        $this->getRedis()->set($this->key, $value);
        return $this;
    }

    public function delete(): self {
        $this->getRedis()->del([$this->getKey()]);
        return $this;
    }

    public function exists(): bool {
        return (bool)$this->getRedis()->exists($this->key);
    }

    public function expire(int $ttl): self {
        $this->getRedis()->expire($this->key, $ttl);
        return $this;
    }

    public function expireAt(int $unixTimestamp): self {
        $this->getRedis()->expireat($this->key, $unixTimestamp);
        return $this;
    }

    private function buildKey(array $parts): string {
        $keyParts = [];
        foreach($parts as $part) {
            $keyParts[] = str_replace('_', ':', $part);
        }

        return implode(':', $keyParts);
    }

}
