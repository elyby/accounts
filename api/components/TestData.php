<?php
namespace api\components;

use Closure;
use Yii;
use yii\base\ActionEvent;
use yii\helpers\Json;
use yii\web\Request;

class TestData {

    private const MAP = [
        'signup/index' => 'beforeSignup',
        'signup/repeat-message' => 'beforeRepeatMessage',
        'signup/confirm' => 'beforeSignupConfirm',
        'authentication/forgot-password' => 'beforeForgotPassword',
        'authentication/recover-password' => 'beforeRecoverPassword',
        'default/get' => 'beforeAccountGet',
        'default/email-verification' => 'beforeAccountEmailVerification',
        'default/new-email-verification' => 'beforeAccountNewEmailVerification',
        'default/email' => 'beforeAccountChangeEmail',
    ];

    public static function getInstance(): callable {
        return Closure::fromCallable([new static(), 'beforeAction']);
    }

    public function beforeAction(ActionEvent $event): void {
        $id = $event->action->controller->id . '/' . $event->action->id;
        if (!isset(self::MAP[$id])) {
            return;
        }

        $handler = self::MAP[$id];
        $request = Yii::$app->request;
        $response = Yii::$app->response;
        $result = $this->$handler($request, $response);
        if ($result === null) {
            return;
        }

        $response->content = Json::encode($result);

        // Prevent request execution
        $event->isValid = false;
        $event->handled = true;
    }

    public function beforeSignup(Request $request): ?array {
        $email = $request->post('email');
        if ($email === 'let-me-register@ely.by') {
            return ['success' => true];
        }

        return null;
    }

    public function beforeRepeatMessage(Request $request): ?array {
        $email = $request->post('email');
        if ($email === 'let-me-register@ely.by' || $email === 'let-me-repeat@ely.by') {
            return ['success' => true];
        }

        return null;
    }

    public function beforeSignupConfirm(Request $request): ?array {
        $email = $request->post('key');
        if ($email === 'LETMEIN') {
            return [
                'success' => true,
                'access_token' => 'dummy_token',
                'expires_in' => time() + 60,
            ];
        }

        return null;
    }

    public function beforeForgotPassword(Request $request): ?array {
        $login = $request->post('login');
        if ($login === 'let-me-recover@ely.by') {
            return [
                'success' => true,
                'data' => [
                    'canRepeatIn' => time() + 60,
                    'repeatFrequency' => 60,
                ],
            ];
        }

        return null;
    }

    public function beforeRecoverPassword(Request $request): ?array {
        $key = $request->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
                'access_token' => 'dummy_token',
                'expires_in' => time() + 60,
            ];
        }

        return null;
    }

    public function beforeAccountGet(Request $request): ?array {
        $httpAuth = $request->getHeaders()->get('authorization');
        if ($httpAuth === 'Bearer dummy_token') {
            return [
                'id' => 1,
                'uuid' => 'f63cd5e1-680f-4c2d-baa2-cc7bb174b71a',
                'username' => 'dummy',
                'isOtpEnabled' => false,
                'registeredAt' => time(),
                'lang' => 'en',
                'elyProfileLink' => 'http://ely.by/u1',
                'email' => 'let-me-register@ely.by',
                'isActive' => true,
                'passwordChangedAt' => time(),
                'hasMojangUsernameCollision' => false,
                'shouldAcceptRules' => false,
            ];
        }

        return null;
    }

    public function beforeAccountEmailVerification(Request $request): ?array {
        $httpAuth = $request->getHeaders()->get('authorization');
        if ($httpAuth === 'Bearer dummy_token') {
            $password = $request->post('password');
            if (empty($password)) {
                return [
                    'success' => false,
                    'errors' => [
                        'password' => 'error.password_required',
                    ],
                ];
            }

            return [
                'success' => true,
            ];
        }

        return null;
    }

    public function beforeAccountNewEmailVerification(Request $request): ?array {
        $key = $request->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
            ];
        }

        return null;
    }

    public function beforeAccountChangeEmail(Request $request): ?array {
        $key = $request->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
                'email' => 'brand-new-email@ely.by',
            ];
        }

        return null;
    }

}
