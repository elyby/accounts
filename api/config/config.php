<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

return [
    'id' => 'accounts-site-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'authserver'],
    'controllerNamespace' => 'api\controllers',
    'params' => $params,
    'components' => [
        'user' => [
            'class' => api\components\User\Component::class,
            'secret' => getenv('JWT_USER_SECRET'),
        ],
        'apiUser' => [
            'class' => api\components\ApiUser\Component::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'legacy-authserver',
                        'session',
                        'api\modules\session\exceptions\SessionServerException:*',
                        'api\modules\authserver\exceptions\AuthserverException:*',
                    ],
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'info'],
                    'categories' => ['legacy-authserver'],
                    'logFile' => '@runtime/logs/authserver.log',
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'info'],
                    'categories' => ['session'],
                    'logFile' => '@runtime/logs/session.log',
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
            'class' => api\components\ReCaptcha\Component::class,
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
        ],
        'oauth' => [
            'class' => common\components\oauth\Component::class,
            'grantTypes' => ['authorization_code'],
        ],
        'errorHandler' => [
            'class' => api\components\ErrorHandler::class,
        ],
    ],
    'modules' => [
        'authserver' => [
            'class' => api\modules\authserver\Module::class,
            'baseDomain' => getenv('AUTHSERVER_HOST'),
        ],
        'session' => [
            'class' => api\modules\session\Module::class,
        ],
    ],
];