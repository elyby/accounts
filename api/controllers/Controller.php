<?php
namespace api\controllers;

use api\traits\ApiNormalize;
use Yii;

/**
 * @property \common\models\Account|null $account
 */
class Controller extends \yii\rest\Controller {
    use ApiNormalize;

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);

        return $parentBehaviors;
    }

    /**
     * @return \common\models\Account|null
     */
    public function getAccount() {
        return Yii::$app->getUser()->getIdentity();
    }

}
