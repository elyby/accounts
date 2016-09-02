<?php
namespace api\modules\session\controllers;

use api\controllers\ApiController;
use api\modules\session\models\JoinForm;

class SessionController extends ApiController {

    public function behaviors() {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        return $behaviors;
    }

    public function actionJoin() {
        $joinForm = new JoinForm();
        $joinForm->loadByPost();
        $joinForm->join();

        return ['id' => 'OK'];
    }

    public function actionJoinLegacy() {

    }

}
