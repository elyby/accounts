<?php
namespace api\aop\aspects;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use Yii;
use yii\web\Request;

class MockDataAspect implements Aspect {

    /**
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public api\controllers\SignupController->actionIndex(*))")
     */
    public function beforeSignup(MethodInvocation $invocation) {
        $email = $this->getRequest()->post('email');
        if ($email === 'let-me-register@ely.by') {
            return ['success' => true];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\controllers\SignupController->actionRepeatMessage(*))")
     */
    public function beforeRepeatMessage(MethodInvocation $invocation) {
        $email = $this->getRequest()->post('email');
        if ($email === 'let-me-register@ely.by' || $email === 'let-me-repeat@ely.by') {
            return ['success' => true];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\controllers\SignupController->actionConfirm(*))")
     */
    public function beforeSignupConfirm(MethodInvocation $invocation) {
        $email = $this->getRequest()->post('key');
        if ($email === 'LETMEIN') {
            return [
                'success' => true,
                'access_token' => 'dummy_token',
                'expires_in' => time() + 60,
            ];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\controllers\AuthenticationController->actionForgotPassword(*))")
     */
    public function beforeForgotPassword(MethodInvocation $invocation) {
        $login = $this->getRequest()->post('login');
        if ($login === 'let-me-recover@ely.by') {
            return [
                'success' => true,
                'data' => [
                    'canRepeatIn' => time() + 60,
                    'repeatFrequency' => 60,
                ],
            ];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\controllers\AuthenticationController->actionRecoverPassword(*))")
     */
    public function beforeRecoverPassword(MethodInvocation $invocation) {
        $key = $this->getRequest()->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
                'access_token' => 'dummy_token',
                'expires_in' => time() + 60,
            ];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\modules\accounts\controllers\DefaultController->actionGet(*))")
     */
    public function beforeAccountGet(MethodInvocation $invocation) {
        $httpAuth = $this->getRequest()->getHeaders()->get('authorization');
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

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\modules\accounts\actions\EmailVerificationAction->run(*))")
     */
    public function beforeAccountEmailVerification(MethodInvocation $invocation) {
        $httpAuth = $this->getRequest()->getHeaders()->get('authorization');
        if ($httpAuth === 'Bearer dummy_token') {
            $password = $this->getRequest()->post('password');
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

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\modules\accounts\actions\NewEmailVerificationAction->run(*))")
     */
    public function beforeAccountNewEmailVerification(MethodInvocation $invocation) {
        $key = $this->getRequest()->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
            ];
        }

        return $invocation->proceed();
    }

    /**
     * @param MethodInvocation $invocation
     * @Around("execution(public api\modules\accounts\actions\ChangeEmailAction->run(*))")
     */
    public function beforeAccountChangeEmail(MethodInvocation $invocation) {
        $key = $this->getRequest()->post('key');
        if ($key === 'LETMEIN') {
            return [
                'success' => true,
                'email' => 'brand-new-email@ely.by',
            ];
        }

        return $invocation->proceed();
    }

    private function getRequest(): Request {
        return Yii::$app->getRequest();
    }

}
