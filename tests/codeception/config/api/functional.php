<?php
$_SERVER['SCRIPT_FILENAME'] = API_ENTRY_FILE;
$_SERVER['SCRIPT_NAME'] = API_ENTRY_URL;

/**
 * Application configuration for api functional tests
 */
return yii\helpers\ArrayHelper::merge(
    require(YII_APP_BASE_PATH . '/common/config/main.php'),
    require(YII_APP_BASE_PATH . '/common/config/main-local.php'), require(YII_APP_BASE_PATH . '/api/config/main.php'),
    require(YII_APP_BASE_PATH . '/api/config/main-local.php'),
    require(dirname(__DIR__) . '/config.php'),
    require(dirname(__DIR__) . '/functional.php'),
    require(__DIR__ . '/config.php'),
    [
    ]
);
