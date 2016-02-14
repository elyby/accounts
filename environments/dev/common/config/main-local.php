<?php
return [
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=localhost;dbname=ely_accounts',
            'username' => 'root',
            'password' => '',
        ],
        'mailer' => [
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'redis' => [
            'hostname' => 'localhost',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
    ],
];
