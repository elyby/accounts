<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'accounts-site-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',
    'components' => [
        'user' => [
            'class' => \api\components\User\Component::class,
            'identityClass' => \api\models\AccountIdentity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => $params['userSecret'],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'request' => [
            'baseUrl' => '/api',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => require __DIR__ . '/routes.php',
        ],
        'reCaptcha' => [
            'class' => 'api\components\ReCaptcha\Component',
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'oauth' => [
            'class' => \common\components\oauth\Component::class,
            'grantTypes' => ['authorization_code'],
        ],
    ],
    'params' => $params,
];
