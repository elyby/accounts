<?php
declare(strict_types=1);

namespace common\tests\_support\Redis;

use InvalidArgumentException;
use yii\base\ArrayAccessTrait;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\redis\Connection;
use yii\test\FileFixtureTrait;
use yii\test\Fixture as BaseFixture;

class Fixture extends BaseFixture {
    use ArrayAccessTrait;
    use FileFixtureTrait;

    public string|Connection $redis = 'redis';

    public $keysPrefix = '';

    public $keysPostfix = '';

    public $data = [];

    public function init(): void {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

    public function load() {
        $this->data = [];
        foreach ($this->getData() as $key => $data) {
            $key = $this->buildKey($key);
            $preparedData = $this->prepareData($data);
            if (is_array($preparedData)) {
                $this->redis->sadd($key, ...$preparedData);
            } else {
                $this->redis->set($key, $preparedData);
            }

            $this->data[$key] = $data;
        }
    }

    public function unload() {
        $this->redis->flushdb();
    }

    protected function getData(): array {
        return $this->loadData($this->dataFile);
    }

    protected function prepareData($input) {
        if (is_string($input)) {
            return $input;
        }

        if (is_int($input) || is_bool($input)) {
            return (string)$input;
        }

        if (is_array($input)) {
            if (!ArrayHelper::isAssociative($input)) {
                return $input;
            }

            return Json::encode($input);
        }

        throw new InvalidArgumentException('Unsupported input type');
    }

    protected function buildKey($key): string {
        return $this->keysPrefix . $key . $this->keysPostfix;
    }

}
