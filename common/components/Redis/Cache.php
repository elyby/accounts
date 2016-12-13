<?php
namespace common\components\Redis;

use yii\di\Instance;

class Cache extends \yii\redis\Cache {

    public function init() {
        \yii\caching\Cache::init();
        $this->redis = Instance::ensure($this->redis, ConnectionInterface::class);
    }

}
