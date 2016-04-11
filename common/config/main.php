<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
        ],
        'security' => [
            'passwordHashStrategy' => 'password_hash',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
        ],
        'amqp' => [
            'class' => \common\components\RabbitMQ\Component::class,
        ],
    ],
];
