<?php
namespace api\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * Поведения:
 * @mixin \yii\filters\ContentNegotiator
 * @mixin \yii\filters\VerbFilter
 * @mixin HttpBearerAuth
 */
class ApiController extends \yii\rest\Controller {

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // Добавляем авторизатор для входа по Bearer токенам
        $parentBehaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->apiUser,
        ];

        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);
        // rate limiter здесь не применяется
        unset($parentBehaviors['rateLimiter']);

        return $parentBehaviors;
    }

}
