<?php
declare(strict_types=1);

namespace api\modules\mojang\controllers;

use api\controllers\Controller;
use api\modules\mojang\behaviors\ServiceErrorConverterBehavior;
use api\rbac\Permissions;
use common\components\SkinsSystemApi;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use function Ramsey\Uuid\v3;

final class ServicesController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['profile'],
                        'roles' => [Permissions::OBTAIN_ACCOUNT_INFO],
                        'roleParams' => function(): array {
                            $account = Yii::$app->user->identity->getAccount();

                            return [
                                'accountId' => $account ? $account->id : -1,
                            ];
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'profile' => ['GET'],
                ],
            ],
        ]);
    }

    public function init(): void {
        parent::init();
        $this->response->attachBehavior('errorFormatter', ServiceErrorConverterBehavior::class);
    }

    public function actionProfile(SkinsSystemApi $skinsSystemApi): array {
        $account = Yii::$app->user->identity->getAccount();
        if ($account === null) {
            throw new NotFoundHttpException();
        }

        try {
            $textures = $skinsSystemApi->textures($account->username);
        } catch (Exception $e) {
            Yii::warning('Cannot get textures from skinsystem.ely.by. Exception message is ' . $e->getMessage());
            $textures = [];
        }

        $response = [
            'id' => str_replace('-', '', $account->uuid),
            'name' => $account->username,
            'skins' => [],
            'capes' => [],
        ];

        if (isset($textures['SKIN'])) {
            $response['skins'][] = [
                'id' => v3(Uuid::NAMESPACE_URL, $textures['SKIN']['url']),
                'state' => 'ACTIVE',
                'url' => $textures['SKIN']['url'],
                'variant' => isset($textures['SKIN']['metadata']['model']) ? 'SLIM' : 'CLASSIC',
                'alias' => '',
            ];
        }

        if (isset($textures['CAPE'])) {
            $response['capes'][] = [
                'id' => v3(Uuid::NAMESPACE_URL, $textures['CAPE']['url']),
                'state' => 'ACTIVE',
                'url' => $textures['CAPE']['url'],
                'alias' => '',
            ];
        }

        return $response;
    }

}
