<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../../common/config/bootstrap.php';

// set correct script paths
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';

Yii::setAlias('@tests', dirname(dirname(__DIR__)));
\Codeception\Specify\Config::setDeepClone(false);
