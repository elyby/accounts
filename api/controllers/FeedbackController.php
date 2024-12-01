<?php
namespace api\controllers;

use api\models\FeedbackForm;
use Yii;
use yii\helpers\ArrayHelper;

class FeedbackController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'optional' => ['index'],
            ],
        ]);
    }

    public function verbs() {
        return [
            'index' => ['POST'],
        ];
    }

    public function actionIndex(): array {
        $model = new FeedbackForm();
        $model->load(Yii::$app->request->post());
        if (!$model->sendMessage()) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

}
