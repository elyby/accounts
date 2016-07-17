<?php
namespace api\controllers;

use api\traits\ApiNormalize;
use yii\filters\auth\HttpBearerAuth;

/**
 * Поведения:
 * @mixin \yii\filters\ContentNegotiator
 * @mixin \yii\filters\VerbFilter
 * @mixin \yii\filters\auth\CompositeAuth
 */
class Controller extends \yii\rest\Controller {
    use ApiNormalize;

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // Добавляем авторизатор для входа по jwt токенам
        $parentBehaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);
        // rate limiter здесь не применяется
        unset($parentBehaviors['rateLimiter']);

        return $parentBehaviors;
    }

}
