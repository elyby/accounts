<?php

use common\config\ConfigLoader;
use yii\web\Application;

$time = microtime(true);

require __DIR__ . '/../vendor/autoload.php';

defined('YII_DEBUG') || define('YII_DEBUG', in_array(getenv('YII_DEBUG'), ['true', '1'], false));
defined('YII_ENV') || define('YII_ENV', getenv('YII_ENV'));

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../common/config/bootstrap.php';
require __DIR__ . '/config/bootstrap.php';

$config = ConfigLoader::load('api');

$application = new Application($config);
$application->run();

$timeDifference = (microtime(true) - $time) * 1000;
fastcgi_finish_request();
Yii::$app->statsd->time('request.time', $timeDifference);
