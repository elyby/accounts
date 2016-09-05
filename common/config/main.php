<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => yii\redis\Cache::class,
            'redis' => 'redis',
        ],
        'db' => [
            'class' => yii\db\Connection::class,
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
        'security' => [
            'passwordHashStrategy' => 'password_hash',
        ],
        'redis' => [
            'class' => yii\redis\Connection::class,
        ],
        'amqp' => [
            'class' => \common\components\RabbitMQ\Component::class,
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
];
