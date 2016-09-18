<?php
return yii\helpers\ArrayHelper::merge(
    \common\config\ConfigLoader::load('console'),
    require __DIR__ . '/../config.php',
    require __DIR__ . '/../unit.php'
);
