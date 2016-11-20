<?php
return [
    'bootstrap' => ['debug', 'gii'],
    'components' => [
        'reCaptcha' => [
            'public' => '6LfwqCYTAAAAAJlaJpqCdzESCjAXUC81Ca6jBHR7',
            'secret' => '6LfwqCYTAAAAAFPjHmgwmnjSMFeAOJh7Lk5xxDMC',
        ],
    ],
    'modules' => [
        'debug' => [
            'class' => yii\debug\Module::class,
            'allowedIPs' => ['*'],
        ],
    ],
];
