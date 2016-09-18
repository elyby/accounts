<?php
new yii\web\Application(require __DIR__ . '/../../config/api/functional.php');

\Codeception\Util\Autoload::registerSuffix('Steps', __DIR__ . DIRECTORY_SEPARATOR);
