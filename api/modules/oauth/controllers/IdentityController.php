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
                                // TODO: find out do I need it at all
                                // The warnings are sent to Sentry by the log target. The token itself isn't logged,
                                // since it's the credential
                                Yii::warning([
                                    'message' => 'Unexpected lack of account',
                                    'identityType' => $identity::class,
                                    'assignedPermissions' => $identity->getAssignedPermissions(),
                                ], __METHOD__);

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
