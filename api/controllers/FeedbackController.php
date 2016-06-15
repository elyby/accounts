<?php
namespace api\controllers;

use api\models\FeedbackForm;
use Yii;
use yii\helpers\ArrayHelper;

class FeedbackController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index'],
            ],
        ]);
    }

    public function verbs() {
        return [
            'index' => ['POST'],
        ];
    }

    public function actionIndex() {
        $model = new FeedbackForm();
        $model->load(Yii::$app->request->post());
        if (!$model->sendMessage()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

}
