<?php
namespace api\modules\session\controllers;

use api\controllers\ApiController;
use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\SessionServerException;
use api\modules\session\models\JoinForm;
use api\modules\session\models\protocols\LegacyJoin;
use api\modules\session\models\protocols\ModernJoin;
use Yii;
use yii\web\Response;

class SessionController extends ApiController {

    public function behaviors() {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        return $behaviors;
    }

    public function actionJoin() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();
        if (empty($data)) {
            // TODO: помнится у Yii2 есть механизм парсинга данных входящего запроса. Лучше будет сделать это там
            $data = json_decode(Yii::$app->request->getRawBody(), true);
        }

        $protocol = new ModernJoin($data['accessToken'] ?? '', $data['selectedProfile'] ?? '', $data['serverId'] ?? '');
        $joinForm = new JoinForm($protocol);
        $joinForm->join();

        return ['id' => 'OK'];
    }

    public function actionJoinLegacy() {
        Yii::$app->response->format = Response::FORMAT_RAW;

        $data = Yii::$app->request->get();
        $protocol = new LegacyJoin($data['user'] ?? '', $data['sessionId'] ?? '', $data['serverId'] ?? '');
        $joinForm = new JoinForm($protocol);
        try {
            $joinForm->join();
        } catch (SessionServerException $e) {
            Yii::$app->response->statusCode = $e->statusCode;
            if ($e instanceof ForbiddenOperationException) {
                $message = 'Ely.by authorization required';
            } else {
                $message = $e->getMessage();
            }

            return $message;
        }

        return 'OK';
    }

}
