<?php
return yii\helpers\ArrayHelper::merge(
    \common\config\ConfigLoader::load('common'),
    require __DIR__ . '/../config.php',
    require __DIR__ . '/../unit.php',
    [
        'id' => 'app-common',
        'basePath' => dirname(__DIR__),
    ]
);
