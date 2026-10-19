<?php
return [
    'id' => 'accounts-site-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'authserver',
        'internal',
        'mojang',
        api\eventListeners\MockDataResponse::class,
        api\eventListeners\LogMetricsToStatsd::class,
    ],
    'controllerNamespace' => 'api\controllers',
    'params' => [
        'authserverHost' => getenv('AUTHSERVER_HOST') ?: 'authserver.ely.by',
    ],
    'modules' => [
        'authserver' => api\modules\authserver\Module::class,
        'session' => api\modules\session\Module::class,
        'mojang' => api\modules\mojang\Module::class,
        'internal' => api\modules\internal\Module::class,
        'accounts' => api\modules\accounts\Module::class,
        'oauth' => api\modules\oauth\Module::class,
    ],
    'container' => [
        'singletons' => [
            api\components\Captcha\CaptchaRegistry::class => function(): api\components\Captcha\CaptchaRegistry {
                $providers = [];
                if (getenv('RECAPTCHA_SECRET') && getenv('RECAPTCHA_PUBLIC') && getenv('RECAPTCHA_PROJECT_ID')) {
                    $providers[api\components\Captcha\Providers\ReCaptchaEnterprise::NAME] = Yii::createObject([
                        '__class' => api\components\Captcha\Providers\ReCaptchaEnterprise::class,
                        '__construct()' => [
                            'apiKey' => getenv('RECAPTCHA_SECRET'),
                            'siteKey' => getenv('RECAPTCHA_PUBLIC'),
                            'projectId' => getenv('RECAPTCHA_PROJECT_ID'),
                        ],
                    ]);
                }

                if (getenv('YANDEX_CAPTCHA_SERVER_KEY') && getenv('YANDEX_CAPTCHA_CLIENT_KEY')) {
                    $providers[api\components\Captcha\Providers\YandexSmartCaptcha::NAME] = Yii::createObject([
                        '__class' => api\components\Captcha\Providers\YandexSmartCaptcha::class,
                        '__construct()' => [
                            'serverKey' => getenv('YANDEX_CAPTCHA_SERVER_KEY'),
                            'clientKey' => getenv('YANDEX_CAPTCHA_CLIENT_KEY'),
                        ],
                    ]);
                }

                if (getenv('ALTCHA_HMAC_SECRET')) {
                    $signatureSecret = getenv('ALTCHA_HMAC_SECRET');
                    // The library treats an empty key secret as a valid HMAC key, so it must be checked explicitly
                    $keySignatureSecret = (string)getenv('ALTCHA_HMAC_KEY_SECRET');
                    Webmozart\Assert\Assert::minLength($signatureSecret, 32, 'ALTCHA_HMAC_SECRET must be at least 32 bytes long');
                    Webmozart\Assert\Assert::minLength($keySignatureSecret, 32, 'ALTCHA_HMAC_KEY_SECRET must be at least 32 bytes long');
                    Webmozart\Assert\Assert::notSame($signatureSecret, $keySignatureSecret, 'ALTCHA_HMAC_SECRET and ALTCHA_HMAC_KEY_SECRET must be different');
                    $cost = (int)(getenv('ALTCHA_COST') ?: 1000);
                    $counterMin = (int)(getenv('ALTCHA_COUNTER_MIN') ?: 500);
                    $counterMax = (int)(getenv('ALTCHA_COUNTER_MAX') ?: 1500);
                    $ttl = (int)(getenv('ALTCHA_TTL') ?: 600);
                    Webmozart\Assert\Assert::positiveInteger($cost, 'ALTCHA_COST must be a positive integer');
                    Webmozart\Assert\Assert::natural($counterMin, 'ALTCHA_COUNTER_MIN must be a non-negative integer');
                    Webmozart\Assert\Assert::natural($counterMax, 'ALTCHA_COUNTER_MAX must be a non-negative integer');
                    Webmozart\Assert\Assert::positiveInteger($ttl, 'ALTCHA_TTL must be a positive integer');

                    $providers[api\components\Captcha\Providers\Altcha::NAME] = Yii::createObject([
                        '__class' => api\components\Captcha\Providers\Altcha::class,
                        '__construct()' => [
                            'altcha' => new AltchaOrg\Altcha\Altcha($signatureSecret, $keySignatureSecret),
                            'algorithm' => new api\components\Captcha\Providers\Altcha\OpensslPbkdf2(),
                            'usedChallenges' => new api\components\Captcha\Providers\Altcha\UsedChallengesStorage(Yii::$app->redis),
                            'cost' => $cost,
                            'counterMin' => $counterMin,
                            'counterMax' => $counterMax,
                            'ttl' => $ttl,
                        ],
                    ]);
                }

                // Not Yii::createObject(): this closure is the container definition of CaptchaRegistry itself
                return new api\components\Captcha\CaptchaRegistry($providers);
            },
        ],
    ],
    'components' => [
        'user' => [
            'class' => api\components\User\Component::class,
        ],
        'tokens' => [
            'class' => api\components\Tokens\Component::class,
            'privateKeyPath' => getenv('JWT_PRIVATE_KEY_PATH') ?: __DIR__ . '/../../data/certs/private.pem',
            'privateKeyPass' => getenv('JWT_PRIVATE_KEY_PASS') ?: null,
            'encryptionKey' => getenv('JWT_ENCRYPTION_KEY'),
        ],
        'tokensFactory' => [
            'class' => api\components\Tokens\TokensFactory::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => nohnaimer\sentry\Target::class,
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
            'enableCsrfCookie' => false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
                'multipart/form-data' => yii\web\MultipartFormDataParser::class,
                '*' => api\request\RequestParser::class,
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => require __DIR__ . '/routes.php',
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
        ],
        'errorHandler' => [
            'class' => api\components\ErrorHandler::class,
        ],
    ],
];
