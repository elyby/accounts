<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\controllers\Controller;
use api\modules\oauth\models\IdentityInfo;
use api\rbac\Permissions as P;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class IdentityController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(Controller::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => [P::OBTAIN_ACCOUNT_INFO],
                        'roleParams' => function(): array {
                            /** @var \api\components\User\IdentityInterface $identity */
                            $identity = Yii::$app->user->getIdentity();
                            $account = $identity->getAccount();
                            if ($account === null) {
                                Yii::$app->sentry->captureMessage('Unexpected lack of account', [
                                    'identityType' => $identity::class,
                                    'userId' => $identity->getId(),
                                    'assignedPermissions' => $identity->getAssignedPermissions(),
                                ], [
                                    'level' => 'warning',
                                ]);

                                return ['accountId' => 0];
                            }

                            return ['accountId' => $account->id];
                        },
                    ],
                ],
            ],
        ]);
    }

    public function actionIndex(): array {
        /** @noinspection NullPointerExceptionInspection */
        return (new IdentityInfo(Yii::$app->user->getIdentity()->getAccount()))->info();
    }

}
