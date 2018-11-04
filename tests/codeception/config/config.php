<?php
return [
    'language' => 'en-US',
    'controllerMap' => [
        'fixture' => [
            'class' => yii\faker\FixtureController::class,
            'fixtureDataPath' => '@tests/codeception/common/fixtures/data',
            'templatePath' => '@tests/codeception/common/templates/fixtures',
            'namespace' => 'tests\codeception\common\fixtures',
        ],
    ],
    'params' => [
        'fromEmail' => 'ely@ely.by',
    ],
    'components' => [
        'urlManager' => [
            'showScriptName' => true,
        ],
        'security' => [
            // Для тестов нам не сильно важна безопасность, а вот время прохождения тестов значительно сокращается
            'passwordHashCost' => 4,
        ],
        'queue' => [
            'class' => tests\codeception\common\_support\queue\Queue::class,
        ],
        'sentry' => [
            'enabled' => false,
        ],
    ],
];
