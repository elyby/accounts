<?php
namespace api\modules\authserver\controllers;

use api\controllers\Controller;
use api\modules\authserver\models;
use Yii;

class AuthenticationController extends Controller {

    public function behaviors(): array {
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
        $model->load(Yii::$app->request->post());

        return $model->authenticate()->getResponseData(true);
    }

    public function actionRefresh() {
        $model = new models\RefreshTokenForm();
        $model->load(Yii::$app->request->post());

        return $model->refresh()->getResponseData(false);
    }

    public function actionValidate() {
        $model = new models\ValidateForm();
        $model->load(Yii::$app->request->post());
        $model->validateToken();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

    public function actionSignout() {
        $model = new models\SignoutForm();
        $model->load(Yii::$app->request->post());
        $model->signout();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

    public function actionInvalidate() {
        $model = new models\InvalidateForm();
        $model->load(Yii::$app->request->post());
        $model->invalidateToken();
        // В случае успеха ожидается пустой ответ. В случае ошибки же бросается исключение,
        // которое обработает ErrorHandler
    }

}
