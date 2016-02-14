<?php
namespace common\components\redis;

use InvalidArgumentException;
use Yii;

class Key {

    protected $key;

    /**
     * @return \yii\redis\Connection
     */
    public function getRedis() {
        return Yii::$app->get('redis');
    }

    public function getKey() {
        return $this->key;
    }

    public function getValue() {
        return $this->getRedis()->get(json_decode($this->key));
    }

    public function setValue($value) {
        $this->getRedis()->set($this->key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $this;
    }

    public function delete() {
        $this->getRedis()->executeCommand('DEL', [$this->key]);
        return $this;
    }

    public function expire($ttl) {
        $this->getRedis()->executeCommand('EXPIRE', [$this->key, $ttl]);
        return $this;
    }

    private function buildKey(array $parts) {
        $keyParts = [];
        foreach($parts as $part) {
            $keyParts[] = str_replace('_', ':', $part);
        }

        return implode(':', $keyParts);
    }

    public function __construct(...$key) {
        if (empty($key)) {
            throw new InvalidArgumentException('You must specify at least one key.');
        }

        $this->key = $this->buildKey($key);
    }

}
