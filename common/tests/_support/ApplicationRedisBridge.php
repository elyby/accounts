<?php
declare(strict_types=1);

namespace common\tests\_support;

use Codeception\Exception\ModuleConfigException;
use Codeception\Module;
use Yii;

class ApplicationRedisBridge extends Module {

    protected $config = [
        'module' => 'Redis',
    ];

    public function _initialize(): void {
        if (!$this->hasModule($this->config['module'])) {
            throw new ModuleConfigException($this, 'This module should be used together with Redis module');
        }

        /** @var \Codeception\Module\Redis $module */
        $module = $this->getModule($this->config['module']);
        $config = $module->_getConfig();
        $config['host'] = Yii::$app->redis->hostname;
        $config['port'] = Yii::$app->redis->port;
        $config['database'] = Yii::$app->redis->database;
        $module->_setConfig($config);
        if ($module->driver !== null) {
            $module->_initialize();
        }
    }

}
