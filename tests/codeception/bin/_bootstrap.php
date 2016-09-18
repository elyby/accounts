<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../../common/config/bootstrap.php';

Yii::setAlias('@tests', dirname(dirname(__DIR__)));
