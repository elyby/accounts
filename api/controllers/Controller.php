<?php
namespace api\controllers;

use api\traits\ApiNormalize;
use Yii;
use yii\filters\auth\HttpBearerAuth;

/**
 * @property \common\models\Account|null $account
 */
class Controller extends \yii\rest\Controller {
    use ApiNormalize;

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // Добавляем авторизатор для входа по jwt токенам
        $parentBehaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);
        // rate limiter здесь не применяется
        unset($parentBehaviors['rateLimiter']);

        return $parentBehaviors;
    }

    /**
     * @return \common\models\Account|null
     */
    public function getAccount() {
        return Yii::$app->getUser()->getIdentity();
    }

}
