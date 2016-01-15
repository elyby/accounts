<?php
namespace api\controllers;

use api\traits\ApiNormalize;

class Controller extends \yii\rest\Controller {
    use ApiNormalize;

    public $enableCsrfValidation = true;

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);

        return $parentBehaviors;
    }

}
