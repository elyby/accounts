<?php
declare(strict_types=1);

namespace api\eventListeners;

use api\controllers\AuthenticationController;
use api\controllers\SignupController;
use api\modules\accounts\actions;
use Closure;
use Yii;
use yii\base\ActionEvent;
use yii\base\BootstrapInterface;
use yii\base\Controller;
use yii\base\Event;

final class LogMetricsToStatsd implements BootstrapInterface {

    public function bootstrap($app): void {
        Event::on(Controller::class, Controller::EVENT_BEFORE_ACTION, Closure::fromCallable([$this, 'beforeAction']));
        Event::on(Controller::class, Controller::EVENT_AFTER_ACTION, Closure::fromCallable([$this, 'afterAction']));
    }

    private function beforeAction(ActionEvent $event): void {
        $prefix = $this->getPrefix($event);
        if ($prefix === null) {
            return;
        }

        Yii::$app->statsd->inc($prefix . '.attempt');
    }

    private function afterAction(ActionEvent $event): void {
        $prefix = $this->getPrefix($event);
        if ($prefix === null) {
            return;
        }

        $result = $event->result;
        if (isset($result['success'])) {
            if ($result['success']) {
                Yii::$app->statsd->inc($prefix . '.success');
            } else {
                $errors = $result['errors'];
                Yii::$app->statsd->inc($prefix . '.' . $errors[array_key_first($errors)]);
            }
        }
    }

    private function getPrefix(ActionEvent $event): ?string {
        $action = $event->action;
        switch ($action::class) {
            case actions\AcceptRulesAction::class: return 'accounts.acceptRules';
            case actions\ChangeEmailAction::class: return 'accounts.changeEmail';
            case actions\ChangeLanguageAction::class: return 'accounts.switchLanguage';
            case actions\ChangePasswordAction::class: return 'accounts.changePassword';
            case actions\ChangeUsernameAction::class: return 'accounts.changeUsername';
            case actions\EnableTwoFactorAuthAction::class: return 'accounts.enableTwoFactorAuth';
            case actions\DisableTwoFactorAuthAction::class: return 'accounts.disableTwoFactorAuth';
            case actions\EmailVerificationAction::class: return 'accounts.sendEmailVerification';
            case actions\NewEmailVerificationAction::class: return 'accounts.sendNewEmailVerification';
        }

        $controller = $action->controller;
        if ($controller instanceof SignupController) {
            switch ($action->id) {
                case 'index': return 'signup.register';
                case 'repeatMessage': return 'signup.repeatEmail';
                case 'confirm': return 'signup.confirmEmail';
            }
        }

        if ($controller instanceof AuthenticationController) {
            switch ($action->id) {
                case 'login': return 'authentication.login';
                case 'logout': return 'authentication.logout';
                case 'forgotPassword': return 'authentication.forgotPassword';
                case 'recoverPassword': return 'authentication.recoverPassword';
                case 'refreshToken': return 'authentication.renew';
            }
        }

        return null;
    }

}
