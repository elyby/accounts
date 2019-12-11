<?php
declare(strict_types=1);

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

    public function verbs(): array {
        return [
            'authenticate' => ['POST'],
            'refresh' => ['POST'],
            'validate' => ['POST'],
            'signout' => ['POST'],
            'invalidate' => ['POST'],
        ];
    }

    /**
     * @return array
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function actionAuthenticate(): array {
        $model = new models\AuthenticationForm();
        $model->load(Yii::$app->request->post());

        return $model->authenticate()->getResponseData(true);
    }

    /**
     * @return array
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function actionRefresh(): array {
        $model = new models\RefreshTokenForm();
        $model->load(Yii::$app->request->post());

        return $model->refresh()->getResponseData(false);
    }

    /**
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function actionValidate(): void {
        $model = new models\ValidateForm();
        $model->load(Yii::$app->request->post());
        $model->validateToken();
        // If successful, an empty answer is expected.
        // In case of an error, an exception is thrown which will be processed by ErrorHandler
    }

    /**
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function actionSignout(): void {
        $model = new models\SignoutForm();
        $model->load(Yii::$app->request->post());
        $model->signout();
        // If successful, an empty answer is expected.
        // In case of an error, an exception is thrown which will be processed by ErrorHandler
    }

    /**
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function actionInvalidate(): void {
        $model = new models\InvalidateForm();
        $model->load(Yii::$app->request->post());
        $model->invalidateToken();
        // If successful, an empty answer is expected.
        // In case of an error, an exception is thrown which will be processed by ErrorHandler
    }

}
