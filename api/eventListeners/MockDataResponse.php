<?php
declare(strict_types=1);

namespace api\eventListeners;

use api\controllers\AuthenticationController;
use api\controllers\SignupController;
use api\modules\accounts\actions\ChangeEmailAction;
use api\modules\accounts\actions\EmailVerificationAction;
use api\modules\accounts\actions\NewEmailVerificationAction;
use api\modules\accounts\controllers\DefaultController;
use Closure;
use yii\base\ActionEvent;
use yii\base\BootstrapInterface;
use yii\base\Controller;
use yii\base\Event;
use yii\web\Response;

final class MockDataResponse implements BootstrapInterface {

    public function bootstrap($app): void {
        Event::on(Controller::class, Controller::EVENT_BEFORE_ACTION, Closure::fromCallable([$this, 'beforeAction']));
    }

    private function beforeAction(ActionEvent $event): void {
        $result = $this->getResponse($event);
        if ($result === null) {
            return;
        }

        /** @var \yii\web\Response $response */
        $response = $event->action->controller->response;
        $response->format = Response::FORMAT_JSON;
        $response->data = $result;

        $event->handled = true;
        $event->isValid = false;
    }

    private function getResponse(ActionEvent $event): ?array {
        $action = $event->action;
        /** @var \yii\web\Controller $controller */
        $controller = $action->controller;
        $request = $controller->request;
        if ($controller instanceof SignupController && $action->id === 'index') {
            $email = $request->post('email');
            if ($email === 'let-me-register@ely.by') {
                return ['success' => true];
            }
        }

        if ($controller instanceof SignupController && $action->id === 'repeatMessage') {
            $email = $request->post('email');
            if ($email === 'let-me-register@ely.by' || $email === 'let-me-repeat@ely.by') {
                return ['success' => true];
            }
        }

        if ($controller instanceof SignupController && $action->id === 'confirm') {
            $key = $request->post('key');
            if ($key === 'LETMEIN') {
                return [
                    'success' => true,
                    'access_token' => 'dummy_token',
                    'expires_in' => time() + 60,
                ];
            }
        }

        if ($controller instanceof AuthenticationController && $action->id === 'forgotPassword') {
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
        }

        if ($controller instanceof AuthenticationController && $action->id === 'recoverPassword') {
            $key = $request->post('key');
            if ($key === 'LETMEIN') {
                return [
                    'success' => true,
                    'access_token' => 'dummy_token',
                    'expires_in' => time() + 60,
                ];
            }
        }

        if ($controller instanceof DefaultController && $action->id === 'get') {
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
        }

        if ($action instanceof EmailVerificationAction) {
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
        }

        if ($action instanceof NewEmailVerificationAction) {
            $key = $request->post('key');
            if ($key === 'LETMEIN') {
                return [
                    'success' => true,
                ];
            }
        }

        if ($action instanceof ChangeEmailAction) {
            $key = $request->post('key');
            if ($key === 'LETMEIN') {
                return [
                    'success' => true,
                    'email' => 'brand-new-email@ely.by',
                ];
            }
        }

        return null;
    }

}
