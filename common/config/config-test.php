<?php
return [
    'id' => 'common-tests',
    'basePath' => dirname(__DIR__),
    'params' => [
        'fromEmail' => 'ely@ely.by',
    ],
    'components' => [
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'security' => [
            // It's allows us to increase tests speed by decreasing password hashing algorithm complexity
            'passwordHashCost' => 4,
        ],
        'queue' => [
            'class' => common\tests\_support\queue\Queue::class,
        ],
        'sentry' => [
            'enabled' => false,
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'emailsRenderer' => [
            'class' => common\tests\_support\EmailsRenderer::class,
        ],
    ],
];
