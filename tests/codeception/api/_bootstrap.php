<?php
use Codeception\Configuration;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

defined('API_ENTRY_URL') or define('API_ENTRY_URL', parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_PATH));
defined('API_ENTRY_FILE') or define('API_ENTRY_FILE', __DIR__ . '/../../../api/web/index.php');

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/../../../common/config/bootstrap.php';
require_once __DIR__ . '/../../../api/config/bootstrap.php';

// set correct script paths

// the entry script file path for functional tests
$_SERVER['SCRIPT_FILENAME'] = API_ENTRY_FILE;
$_SERVER['SCRIPT_NAME'] = API_ENTRY_URL;
$_SERVER['SERVER_NAME'] = parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_HOST);
$_SERVER['SERVER_PORT'] = parse_url(Configuration::config()['config']['test_entry_url'], PHP_URL_PORT) ?: '80';

Yii::setAlias('@tests', dirname(__DIR__, 2));
