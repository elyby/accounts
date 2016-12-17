<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/params.php'
);

return [
    'id' => 'accounts-site-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'authserver', 'internal'],
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
                    'class' => mito\sentry\Target::class,
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'legacy-authserver',
                        'session',
                        'yii\web\HttpException:*',
                        'api\modules\session\exceptions\SessionServerException:*',
                        'api\modules\authserver\exceptions\AuthserverException:*',
                    ],
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'legacy-authserver',
                        'session',
                        'yii\web\HttpException:*',
                        'api\modules\session\exceptions\SessionServerException:*',
                        'api\modules\authserver\exceptions\AuthserverException:*',
                    ],
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'info'],
                    'logVars' => [],
                    'categories' => ['legacy-authserver'],
                    'logFile' => '@runtime/logs/authserver.log',
                ],
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'info'],
                    'logVars' => [],
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
            'class' => api\components\OAuth2\Component::class,
            'grantTypes' => ['authorization_code', 'client_credentials'],
            'grantMap' => [
                'authorization_code' => api\components\OAuth2\Grants\AuthCodeGrant::class,
                'refresh_token' => api\components\OAuth2\Grants\RefreshTokenGrant::class,
                'client_credentials' => api\components\OAuth2\Grants\ClientCredentialsGrant::class,
            ],
        ],
        'errorHandler' => [
            'class' => api\components\ErrorHandler::class,
        ],
    ],
    'modules' => [
        'authserver' => [
            'class' => api\modules\authserver\Module::class,
            'host' => $params['authserverHost'],
        ],
        'session' => [
            'class' => api\modules\session\Module::class,
        ],
        'mojang' => [
            'class' => api\modules\mojang\Module::class,
        ],
        'internal' => [
            'class' => api\modules\internal\Module::class,
        ],
    ],
];
