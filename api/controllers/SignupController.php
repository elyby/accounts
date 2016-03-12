<?php
namespace api\controllers;

use api\models\ConfirmEmailForm;
use api\models\NewAccountActivationForm;
use api\models\RegistrationForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class SignupController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index', 'new-message', 'confirm'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'new-message', 'confirm'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'register' => ['POST'],
            'confirm' => ['POST'],
            'new-message' => ['POST'],
        ];
    }

    public function actionIndex() {
        $model = new RegistrationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->signup()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionNewMessage() {
        $model = new NewAccountActivationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->sendNewMessage()) {
            $response = [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];

            if ($response['errors']['email'] === 'error.recently_sent_message') {
                $activeActivation = $model->getActiveActivation();
                $response['data'] = [
                    'can_repeat_in' => $activeActivation->created_at - time() + NewAccountActivationForm::REPEAT_FREQUENCY,
                    'repeat_frequency' => NewAccountActivationForm::REPEAT_FREQUENCY,
                ];
            }

            return $response;
        }

        return [
            'success' => true,
        ];
    }

    public function actionConfirm() {
        $model = new ConfirmEmailForm();
        $model->load(Yii::$app->request->post());
        if (!($jwt = $model->confirm())) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
            'jwt' => $jwt,
        ];
    }

}
