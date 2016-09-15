<?php
require __DIR__ . '/../../vendor/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', (boolean)getenv('YII_DEBUG'));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV'));
defined('YII_APPLICATION_TYPE') or define('YII_APPLICATION_TYPE', 'web');
defined('YII_APPLICATION_MODULE') or define('YII_APPLICATION_MODULE', 'api');

require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = require __DIR__ . './../../common/config/config-loader.php';

$application = new yii\web\Application($config);
$application->run();
