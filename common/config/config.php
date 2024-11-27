<?php

use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

return [
    'version' => '{{PLACE_VERSION_HERE}}', // This will be replaced by build tool
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',

        '@root' => dirname(__DIR__, 2),
        '@api' => '@root/api',
        '@common' => '@root/common',
        '@console' => '@root/console',
    ],
    'params' => [
        'fromEmail' => 'account@ely.by',
        'supportEmail' => 'support@ely.by',
    ],
    'container' => [
        'singletons' => [
            GuzzleHttp\ClientInterface::class => GuzzleHttp\Client::class,
            Ely\Mojang\Api::class => Ely\Mojang\Api::class,
            common\components\SkinsSystemApi::class => [
                'class' => common\components\SkinsSystemApi::class,
                '__construct()' => [
                    'http://' . (getenv('CHRLY_HOST') ?: 'skinsystem.ely.by'),
                ],
            ],
        ],
    ],
    'components' => [
        'cache' => [
            'class' => yii\redis\Cache::class,
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
            'class' => yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => SmtpTransport::class,
                'host' => getenv('SMTP_HOST'),
                'username' => getenv('SMTP_USER'),
                'password' => getenv('SMTP_PASS'),
                'port' => getenv('SMTP_PORT') ?: 587,
                'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            ],
        ],
        'sentry' => [
            'class' => common\components\Sentry::class,
            'enabled' => !empty(getenv('SENTRY_DSN')),
            'dsn' => getenv('SENTRY_DSN'),
            'environment' => (function(): string {
                if (!empty(getenv('SENTRY_ENV'))) {
                    return getenv('SENTRY_ENV');
                }

                return YII_ENV_DEV ? 'Development' : 'Production';
            })(),
        ],
        'security' => [
            'passwordHashStrategy' => 'password_hash',
        ],
        'redis' => [
            'class' => yii\redis\Connection::class,
            'hostname' => getenv('REDIS_HOST') ?: 'redis',
            'password' => getenv('REDIS_PASS') ?: null,
            'port' => getenv('REDIS_PORT') ?: 6379,
            'database' => getenv('REDIS_DATABASE') ?: 0,
        ],
        'guzzle' => [
            'class' => GuzzleHttp\Client::class,
        ],
        'emailsRenderer' => [
            'class' => common\components\EmailsRenderer\Component::class,
            'serviceUrl' => getenv('EMAILS_RENDERER_HOST') ?: 'http://emails-renderer:3000',
            'basePath' => '/images/emails',
        ],
        'authManager' => [
            'class' => \api\rbac\Manager::class,
            'itemFile' => '@api/rbac/.generated/items.php',
            'ruleFile' => '@api/rbac/.generated/rules.php',
        ],
        'statsd' => [
            'class' => common\components\StatsD::class,
            'host' => getenv('STATSD_HOST'),
            'port' => getenv('STATSD_PORT') ?: 8125,
            'namespace' => getenv('STATSD_NAMESPACE') ?: 'ely.accounts.' . gethostname() . '.app',
        ],
        'queue' => [
            'class' => yii\queue\redis\Queue::class,
        ],
    ],
];
