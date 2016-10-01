<?php
$_SERVER['SCRIPT_FILENAME'] = API_ENTRY_FILE;
$_SERVER['SCRIPT_NAME'] = API_ENTRY_URL;

return yii\helpers\ArrayHelper::merge(
    \common\config\ConfigLoader::load('api'),
    require __DIR__ . '/../config.php',
    require __DIR__ . '/../functional.php',
    require __DIR__ . '/config.php'
);
