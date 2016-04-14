<?php
return [
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=localhost;dbname=ely_accounts',
            'username' => 'root',
            'password' => '',
        ],
        'redis' => [
            'hostname' => 'localhost',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
        'amqp' => [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'root',
            'password' => '',
            'vhost' => '/',
        ],
    ],
];
