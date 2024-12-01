<?php
namespace api\controllers;

use api\models\authentication\ConfirmEmailForm;
use api\models\authentication\RegistrationForm;
use api\models\authentication\RepeatAccountActivationForm;
use common\helpers\Error as E;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class SignupController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index', 'repeat-message', 'confirm'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'repeat-message', 'confirm'],
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

    public function actionIndex(): array {
        $model = new RegistrationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->signup()) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionRepeatMessage(): array {
        $model = new RepeatAccountActivationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->sendRepeatMessage()) {
            $response = [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];

            if (ArrayHelper::getValue($response['errors'], 'email') === E::RECENTLY_SENT_MESSAGE) {
                /** @var \common\models\confirmations\RegistrationConfirmation $activation */
                $activation = $model->getActivation();
                $response['data'] = [
                    'canRepeatIn' => $activation->canResendAt(),
                ];
            }

            return $response;
        }

        return [
            'success' => true,
        ];
    }

    public function actionConfirm(): array {
        $model = new ConfirmEmailForm();
        $model->load(Yii::$app->request->post());
        if (!($result = $model->confirm())) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return array_merge([
            'success' => true,
        ], $result->formatAsOAuth2Response());
    }

}
