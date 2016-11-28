<?php
namespace common\components\Redis;

use InvalidArgumentException;
use Yii;

class Key {

    protected $key;

    /**
     * @return Connection
     */
    public function getRedis() {
        return Yii::$app->redis;
    }

    public function getKey() : string {
        return $this->key;
    }

    public function getValue() {
        return $this->getRedis()->get($this->key);
    }

    public function setValue($value) {
        $this->getRedis()->set($this->key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $this;
    }

    public function delete() {
        $this->getRedis()->del($this->key);
        return $this;
    }

    public function exists() : bool {
        return (bool)$this->getRedis()->exists($this->key);
    }

    public function expire($ttl) {
        $this->getRedis()->expire($this->key, $ttl);
        return $this;
    }

    public function __construct(...$key) {
        if (empty($key)) {
            throw new InvalidArgumentException('You must specify at least one key.');
        }

        $this->key = $this->buildKey($key);
    }

    private function buildKey(array $parts) {
        $keyParts = [];
        foreach($parts as $part) {
            $keyParts[] = str_replace('_', ':', $part);
        }

        return implode(':', $keyParts);
    }

}
