<?php
namespace api\modules\login\controllers;

use api\controllers\Controller;

class DefaultController extends Controller {

    public function actionIndex() {
        return ['hello' => 'world'];
    }

}
