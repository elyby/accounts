<?php
return [
    'id' => 'accounts-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'console\controllers',
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
        'urlManager' => [
            'hostInfo' => getenv('DOMAIN') ?: 'https://account.ely.by',
        ],
        'queue' => [
            'on afterError' => [new console\components\ErrorHandler(), 'handleQueueError'],
        ],
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'templateFile' => '@console/views/migration.php',
        ],
    ],
];
