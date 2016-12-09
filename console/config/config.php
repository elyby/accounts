<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

return [
    'id' => 'accounts-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'params' => $params,
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => mito\sentry\Target::class,
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'controllerMap' => [
        'migrate' => [
            'class'        => yii\console\controllers\MigrateController::class,
            'templateFile' => '@console/views/migration.php',
        ],
    ],
];
