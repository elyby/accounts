<?php
return [
    'id' => 'common-tests',
    'basePath' => dirname(__DIR__),
    'params' => [
        'fromEmail' => 'ely@ely.by',
    ],
    'components' => [
        // The tests wipe all the data, so they use separate databases on the same servers.
        // The MariaDB database is created and migrated automatically (see common\tests\_support\TestDatabase)
        'db' => [
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';dbname=' . (getenv('DB_TEST_DATABASE') ?: 'test_' . getenv('DB_DATABASE')),
        ],
        'unbufferedDb' => [
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';dbname=' . (getenv('DB_TEST_DATABASE') ?: 'test_' . getenv('DB_DATABASE')),
        ],
        'redis' => [
            'database' => getenv('REDIS_TEST_DATABASE') ?: 9,
        ],
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'security' => [
            // It's allows us to increase tests speed by decreasing password hashing algorithm complexity
            'passwordHashCost' => 4,
        ],
        'queue' => [
            'class' => common\tests\_support\queue\Queue::class,
        ],
        'sentry' => [
            'enabled' => false,
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'emailsRenderer' => [
            'class' => common\tests\_support\EmailsRenderer::class,
        ],
    ],
];
