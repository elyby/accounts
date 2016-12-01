<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => common\components\Redis\Cache::class,
            'redis' => 'redis',
        ],
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=db;dbname=' . getenv('MYSQL_DATABASE'),
            'username' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASSWORD'),
            'charset' => 'utf8',
            'schemaMap' => [
                'mysql' => common\db\mysql\Schema::class,
            ],
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
        'sentry' => [
            'class' => mito\sentry\SentryComponent::class,
            'enabled' => !empty(getenv('SENTRY_DSN')),
            'dsn' => getenv('SENTRY_DSN'),
            'environment' => YII_ENV_DEV ? 'development' : 'production',
            'jsNotifier' => false,
            'client' => [
                'curl_method' => 'async',
            ],
        ],
        'security' => [
            'passwordHashStrategy' => 'password_hash',
        ],
        'redis' => [
            'class' => common\components\Redis\Connection::class,
            'hostname' => 'redis',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
        'amqp' => [
            'class' => common\components\RabbitMQ\Component::class,
            'host' => 'rabbitmq',
            'port' => 5672,
            'user' => getenv('RABBITMQ_DEFAULT_USER'),
            'password' => getenv('RABBITMQ_DEFAULT_PASS'),
            'vhost' => getenv('RABBITMQ_DEFAULT_VHOST'),
        ],
        'guzzle' => [
            'class' => GuzzleHttp\Client::class,
        ],
        'emailRenderer' => [
            'class' => common\components\EmailRenderer::class,
            'basePath' => '/images/emails',
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
];
