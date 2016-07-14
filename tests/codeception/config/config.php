<?php
return [
    'language' => 'en-US',
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\faker\FixtureController',
            'fixtureDataPath' => '@tests/codeception/common/fixtures/data',
            'templatePath' => '@tests/codeception/common/templates/fixtures',
            'namespace' => 'tests\codeception\common\fixtures',
        ],
    ],
    'components' => [
        'db' => [
            'dsn' => 'mysql:host=testdb;dbname=ely_accounts_test',
            'username' => 'ely_accounts_tester',
            'password' => 'ely_accounts_tester_password',
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'redis' => [
            'hostname' => 'testredis',
        ],
        'amqp' => [
            'host' => 'testrabbit',
            'user' => 'ely-accounts-tester',
            'password' => 'tester-password',
            'vhost' => '/account.ely.by/tests',
        ],
    ],
];
