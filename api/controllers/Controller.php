<?php
namespace api\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * Поведения:
 * @mixin \yii\filters\ContentNegotiator
 * @mixin \yii\filters\VerbFilter
 * @mixin \yii\filters\auth\CompositeAuth
 */
class Controller extends \yii\rest\Controller {

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // Добавляем авторизатор для входа по jwt токенам
        $parentBehaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->getUser(),
        ];

        // xml и rate limiter нам не понадобятся
        unset(
            $parentBehaviors['contentNegotiator']['formats']['application/xml'],
            $parentBehaviors['rateLimiter']
        );

        return $parentBehaviors;
    }

}
