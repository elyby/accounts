<?php
return [
    'version' => '1.1.25-dev',
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'components' => [
        'cache' => [
            'class' => common\components\Redis\Cache::class,
            'redis' => 'redis',
        ],
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';dbname=' . getenv('DB_DATABASE'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'schemaMap' => [
                'mysql' => common\db\mysql\Schema::class,
            ],
        ],
        'unbufferedDb' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';dbname=' . getenv('DB_DATABASE'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'attributes' => [
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ],
            'schemaMap' => [
                'mysql' => common\db\mysql\Schema::class,
            ],
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => Swift_SmtpTransport::class,
                'host' => 'ely.by',
                'username' => getenv('SMTP_USER'),
                'password' => getenv('SMTP_PASS'),
                'port' => getenv('SMTP_PORT') ?: 587,
                'encryption' => 'tls',
                'streamOptions' => [
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                    ],
                ],
            ],
        ],
        'sentry' => [
            'class' => common\components\Sentry\Component::class,
            'enabled' => !empty(getenv('SENTRY_DSN')),
            'dsn' => getenv('SENTRY_DSN'),
            'environment' => YII_ENV_DEV ? 'development' : 'production',
            'client' => [
                'curl_method' => 'async',
            ],
        ],
        'security' => [
            'passwordHashStrategy' => 'password_hash',
        ],
        'redis' => [
            'class' => common\components\Redis\Connection::class,
            'hostname' => getenv('REDIS_HOST') ?: 'redis',
            'password' => getenv('REDIS_PASS') ?: null,
            'port' => getenv('REDIS_PORT') ?: 6379,
            'database' => getenv('REDIS_DATABASE') ?: 0,
        ],
        'amqp' => [
            'class' => common\components\RabbitMQ\Component::class,
            'host' => getenv('RABBITMQ_HOST') ?: 'rabbitmq',
            'port' => getenv('RABBITMQ_PORT') ?: 5672,
            'user' => getenv('RABBITMQ_USER'),
            'password' => getenv('RABBITMQ_PASS'),
            'vhost' => getenv('RABBITMQ_VHOST'),
        ],
        'guzzle' => [
            'class' => GuzzleHttp\Client::class,
        ],
        'emailRenderer' => [
            'class' => common\components\EmailRenderer::class,
            'basePath' => '/images/emails',
        ],
        'oauth' => [
            'class' => api\components\OAuth2\Component::class,
        ],
        'authManager' => [
            'class' => common\rbac\Manager::class,
            'itemFile' => '@common/rbac/.generated/items.php',
            'ruleFile' => '@common/rbac/.generated/rules.php',
        ],
        'statsd' => [
            'class' => common\components\StatsD::class,
            'host' => getenv('STATSD_HOST'),
            'port' => getenv('STATSD_PORT') ?: 8125,
            'namespace' => getenv('STATSD_NAMESPACE') ?: 'ely.accounts.' . gethostname() . '.app',
        ],
        'queue' => [
            'class' => yii\queue\amqp_interop\Queue::class,
            'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,
            'host' => getenv('RABBITMQ_HOST') ?: 'rabbitmq',
            'port' => getenv('RABBITMQ_PORT') ?: 5672,
            'user' => getenv('RABBITMQ_USER'),
            'password' => getenv('RABBITMQ_PASS'),
            'vhost' => getenv('RABBITMQ_VHOST'),
            'queueName' => 'worker',
            'exchangeName' => 'tasks',
        ],
    ],
    'container' => [
        'definitions' => [
            GuzzleHttp\ClientInterface::class => GuzzleHttp\Client::class,
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
];
