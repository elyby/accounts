<?php
require __DIR__ . '/../../vendor/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', in_array(getenv('YII_DEBUG'), ['false', '1']));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV'));

require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = \common\config\ConfigLoader::load('api');

$application = new yii\web\Application($config);
$application->run();
