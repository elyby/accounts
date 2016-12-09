<?php
namespace common\components\Annotations;

use common\components\Redis\Key;
use common\components\Redis\Set;
use Minime\Annotations\Interfaces\CacheInterface;
use yii\helpers\Json;

class RedisCache implements CacheInterface {

    /**
     * Generates uuid for a given docblock string
     * @param  string $docblock docblock string
     * @return string uuid that maps to the given docblock
     */
    public function getKey($docblock) {
        return md5($docblock);
    }

    /**
     * Adds an annotation AST to cache
     *
     * @param string $key cache entry uuid
     * @param array  $annotations annotation AST
     */
    public function set($key, array $annotations) {
        $this->getRedisKey($key)->setValue(Json::encode($annotations))->expire(3600);
        $this->getRedisKeysSet()->add($key);
    }

    /**
     * Retrieves cached annotations from docblock uuid
     *
     * @param  string $key cache entry uuid
     * @return array  cached annotation AST
     */
    public function get($key) {
        $result = $this->getRedisKey($key)->getValue();
        if ($result === null) {
            return [];
        }

        return Json::decode($result);
    }

    /**
     * Resets cache
     */
    public function clear() {
        /** @var array $keys */
        $keys = $this->getRedisKeysSet()->getValue();
        foreach ($keys as $key) {
            $this->getRedisKey($key)->delete();
        }
    }

    private function getRedisKey(string $key): Key {
        return new Key('annotations', 'cache', $key);
    }

    private function getRedisKeysSet(): Set {
        return new Set('annotations', 'cache', 'keys');
    }

}
