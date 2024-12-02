<?php
declare(strict_types=1);

namespace api\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * Behaviors:
 * @mixin \yii\filters\ContentNegotiator
 * @mixin \yii\filters\VerbFilter
 * @mixin \yii\filters\auth\CompositeAuth
 */
class Controller extends \yii\rest\Controller {

    public function behaviors(): array {
        $parentBehaviors = parent::behaviors();
        // Add JWT authenticator
        $parentBehaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'user' => Yii::$app->getUser(),
        ];

        // XML and rate limiter is not necessary
        unset(
            $parentBehaviors['contentNegotiator']['formats']['application/xml'],
            $parentBehaviors['rateLimiter'],
        );

        return $parentBehaviors;
    }

}
