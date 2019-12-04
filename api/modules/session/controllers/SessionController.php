<?php
declare(strict_types=1);

namespace api\modules\session\controllers;

use api\controllers\Controller;
use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\exceptions\SessionServerException;
use api\modules\session\filters\RateLimiter;
use api\modules\session\models\HasJoinedForm;
use api\modules\session\models\JoinForm;
use api\modules\session\models\protocols\LegacyJoin;
use api\modules\session\models\protocols\ModernHasJoined;
use api\modules\session\models\protocols\ModernJoin;
use common\models\Account;
use common\models\Textures;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\web\Response;

class SessionController extends Controller {

    public function behaviors(): array {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['rateLimiting'] = [
            'class' => RateLimiter::class,
            'only' => ['has-joined', 'has-joined-legacy'],
            'authserverDomain' => Yii::$app->params['authserverHost'],
        ];

        return $behaviors;
    }

    /**
     * @return array
     * @throws ForbiddenOperationException
     * @throws IllegalArgumentException
     */
    public function actionJoin(): array {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();
        $protocol = new ModernJoin($data['accessToken'] ?? '', $data['selectedProfile'] ?? '', $data['serverId'] ?? '');
        $joinForm = new JoinForm($protocol);
        $joinForm->join(); // will throw an exception in case of any error

        return ['id' => 'OK'];
    }

    public function actionJoinLegacy(): string {
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

    /**
     * @return array
     * @throws ForbiddenOperationException
     * @throws IllegalArgumentException
     */
    public function actionHasJoined(): array {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->get();
        $protocol = new ModernHasJoined($data['username'] ?? '', $data['serverId'] ?? '');
        $hasJoinedForm = new HasJoinedForm($protocol);
        $account = $hasJoinedForm->hasJoined();
        $textures = new Textures($account);

        return $textures->getMinecraftResponse();
    }

    public function actionHasJoinedLegacy(): string {
        Yii::$app->response->format = Response::FORMAT_RAW;

        $data = Yii::$app->request->get();
        $protocol = new ModernHasJoined($data['user'] ?? '', $data['serverId'] ?? '');
        $hasJoinedForm = new HasJoinedForm($protocol);
        try {
            $hasJoinedForm->hasJoined();
        } catch (ForbiddenOperationException $e) {
            return 'NO';
        } catch (SessionServerException $e) {
            Yii::$app->response->statusCode = $e->statusCode;

            return $e->getMessage();
        }

        return 'YES';
    }

    /**
     * @param string $uuid
     *
     * @return array
     * @throws ForbiddenOperationException
     * @throws IllegalArgumentException
     */
    public function actionProfile(string $uuid): array {
        try {
            $uuid = Uuid::fromString($uuid)->toString();
        } catch (\InvalidArgumentException $e) {
            throw new IllegalArgumentException('Invalid uuid format.');
        }

        $account = Account::findOne(['uuid' => $uuid]);
        if ($account === null) {
            throw new ForbiddenOperationException('Invalid uuid.');
        }

        return (new Textures($account))->getMinecraftResponse();
    }

}
