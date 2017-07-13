<?php
return [
    'version' => '1.1.16-dev',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
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
            'charset' => 'utf8',
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
            'grantTypes' => ['authorization_code', 'client_credentials'],
            'grantMap' => [
                'authorization_code' => api\components\OAuth2\Grants\AuthCodeGrant::class,
                'refresh_token' => api\components\OAuth2\Grants\RefreshTokenGrant::class,
                'client_credentials' => api\components\OAuth2\Grants\ClientCredentialsGrant::class,
            ],
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
