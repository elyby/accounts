<?php
namespace api\modules\authserver\controllers;

use api\controllers\Controller;
use api\modules\authserver\models;

class AuthenticationController extends Controller {

    public function behaviors() {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        return $behaviors;
    }

    public function verbs() {
        return [
            'authenticate' => ['POST'],
            'refresh'      => ['POST'],
            'validate'     => ['POST'],
            'signout'      => ['POST'],
            'invalidate'   => ['POST'],
        ];
    }

    public function actionAuthenticate() {
        $model = new models\AuthenticationForm();
        $model->loadByPost();

        return $model->authenticate()->getResponseData(true);
    }

    public function actionRefresh() {
        $model = new models\RefreshTokenForm();
        $model->loadByPost();

        return $model->refresh()->getResponseData(false);
    }

    public function actionValidate() {
        $model = new models\ValidateForm();
        $model->loadByPost();
        $model->validateToken();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

    public function actionSignout() {
        $model = new models\SignoutForm();
        $model->loadByPost();
        $model->signout();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

    public function actionInvalidate() {
        $model = new models\InvalidateForm();
        $model->loadByPost();
        $model->invalidateToken();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

}
