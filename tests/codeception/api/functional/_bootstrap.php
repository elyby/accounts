<?php

new yii\web\Application(require(dirname(dirname(__DIR__)) . '/config/api/functional.php'));

\Codeception\Util\Autoload::registerSuffix('Steps', __DIR__ . DIRECTORY_SEPARATOR);
