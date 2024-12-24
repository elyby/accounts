<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\controllers\Controller;
use api\modules\oauth\models\OauthProcess;
use api\rbac\Permissions as P;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Yii;
use yii\base\Module;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

final class AuthorizationController extends Controller {

    public function __construct(
        string $id,
        Module $module,
        private readonly OauthProcess $oauthProcess,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array {
        return ArrayHelper::merge(Controller::behaviors(), [
            'authenticator' => [
                'only' => ['complete'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['complete'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['complete'],
                        'roles' => [P::COMPLETE_OAUTH_FLOW],
                        'roleParams' => fn(): array => [
                            'accountId' => Yii::$app->user->identity->getAccount()->id,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function verbs(): array {
        return [
            'validate' => ['GET'],
            'complete' => ['POST'],
            'devicecode' => ['POST'],
            'token' => ['POST'],
        ];
    }

    public function actionValidate(): array {
        return $this->oauthProcess->validate($this->getServerRequest());
    }

    public function actionComplete(): array {
        return $this->oauthProcess->complete($this->getServerRequest());
    }

    public function actionDevicecode(): array {
        return $this->oauthProcess->deviceCode($this->getServerRequest());
    }

    public function actionToken(): array {
        return $this->oauthProcess->getToken($this->getServerRequest());
    }

    private function getServerRequest(): ServerRequestInterface {
        $request = Yii::$app->request;

        return (new ServerRequest(
            $request->getMethod(),
            $request->getAbsoluteUrl() . '?' . $request->getQueryString(),
            $request->getHeaders()->toArray(),
        ))
            ->withParsedBody($request->getBodyParams())
            ->withQueryParams($request->getQueryParams());
    }

}
