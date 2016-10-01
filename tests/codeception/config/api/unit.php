<?php
return yii\helpers\ArrayHelper::merge(
    \common\config\ConfigLoader::load('api'),
    require __DIR__ . '/../config.php',
    require __DIR__ . '/../unit.php',
    require __DIR__ . '/config.php'
);
