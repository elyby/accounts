<?php
return [
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=db;dbname=ely_accounts',
            'username' => 'ely_accounts_user',
            'password' => 'ely_accounts_password',
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'redis' => [
            'hostname' => 'redis',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
        'amqp' => [
            'host' => 'rabbitmq',
            'port' => 5672,
            'user' => 'ely-accounts-app',
            'password' => 'app-password',
            'vhost' => '/account.ely.by',
        ],
    ],
];
