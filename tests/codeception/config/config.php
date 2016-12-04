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
    'components' => [
        'urlManager' => [
            'showScriptName' => true,
        ],
        'security' => [
            // Для тестов нам не сильно важна безопасность, а вот время прохождения тестов значительно сокращается
            'passwordHashCost' => 4,
        ],
        'amqp' => [
            'class' => tests\codeception\common\_support\RabbitMQComponent::class,
        ],
    ],
];
