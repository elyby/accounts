<?php
namespace api\controllers;


class Controller extends \yii\rest\Controller {

    public $enableCsrfValidation = true;

    public function behaviors() {
        $parentBehaviors = parent::behaviors();
        // xml нам не понадобится
        unset($parentBehaviors['contentNegotiator']['formats']['application/xml']);

        return $parentBehaviors;
    }

}
