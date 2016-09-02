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
    'bootstrap' => ['log', 'authserver', 'sessionserver'],
    'controllerNamespace' => 'api\controllers',
    'params' => $params,
    'components' => [
        'user' => [
            'class' => \api\components\User\Component::class,
            'secret' => $params['userSecret'],
        ],
        'apiUser' => [
            'class' => \api\components\ApiUser\Component::class,
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
        'request' => [
            'baseUrl' => '/api',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => require __DIR__ . '/routes.php',
        ],
        'reCaptcha' => [
            'class' => \api\components\ReCaptcha\Component::class,
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
        ],
        'oauth' => [
            'class' => \common\components\oauth\Component::class,
            'grantTypes' => ['authorization_code'],
        ],
        'errorHandler' => [
            'class' => \api\components\ErrorHandler::class,
        ],
    ],
    'modules' => [
        'authserver' => [
            'class' => \api\modules\authserver\Module::class,
            'baseDomain' => $params['authserverDomain'],
        ],
        'sessionserver' => [
            'class' => \api\modules\session\Module::class,
        ],
    ],
];
