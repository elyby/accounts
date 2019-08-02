<?php
namespace api\modules\accounts\controllers;

use api\controllers\Controller;
use api\modules\accounts\actions;
use api\modules\accounts\models\AccountInfo;
use api\modules\accounts\models\TwoFactorAuthInfo;
use api\rbac\Permissions as P;
use common\models\Account;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller {

    public function behaviors(): array {
        $paramsCallback = function(): array {
            $id = (int)Yii::$app->request->get('id');
            if ($id === 0) {
                $identity = Yii::$app->user->getIdentity();
                if ($identity !== null) {
                    $account = $identity->getAccount();
                    if ($account !== null) {
                        $id = $account->id;
                    }
                }
            }

            return [
                'accountId' => $id,
            ];
        };

        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['get'],
                        'roles' => [P::OBTAIN_ACCOUNT_INFO],
                        'roleParams' => function() use ($paramsCallback) {
                            return array_merge($paramsCallback(), [
                                'optionalRules' => true,
                            ]);
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['username'],
                        'roles' => [P::CHANGE_ACCOUNT_USERNAME],
                        'roleParams' => $paramsCallback,
                    ],
                    [
                        'allow' => true,
                        'actions' => ['password'],
                        'roles' => [P::CHANGE_ACCOUNT_PASSWORD],
                        'roleParams' => $paramsCallback,
                    ],
                    [
                        'allow' => true,
                        'actions' => ['language'],
                        'roles' => [P::CHANGE_ACCOUNT_LANGUAGE],
                        'roleParams' => $paramsCallback,
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'email',
                            'email-verification',
                            'new-email-verification',
                        ],
                        'roles' => [P::CHANGE_ACCOUNT_EMAIL],
                        'roleParams' => $paramsCallback,
                    ],
                    [
                        'allow' => true,
                        'actions' => ['rules'],
                        'roles' => [P::ACCEPT_NEW_PROJECT_RULES],
                        'roleParams' => function() use ($paramsCallback) {
                            return array_merge($paramsCallback(), [
                                'optionalRules' => true,
                            ]);
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'get-two-factor-auth-credentials',
                            'enable-two-factor-auth',
                            'disable-two-factor-auth',
                        ],
                        'roles' => [P::MANAGE_TWO_FACTOR_AUTH],
                        'roleParams' => $paramsCallback,
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'ban',
                            'pardon',
                        ],
                        'roles' => [P::BLOCK_ACCOUNT],
                        'roleParams' => $paramsCallback,
                    ],
                ],
            ],
        ]);
    }

    public function actions(): array {
        return [
            'username' => actions\ChangeUsernameAction::class,
            'password' => actions\ChangePasswordAction::class,
            'language' => actions\ChangeLanguageAction::class,
            'email' => actions\ChangeEmailAction::class,
            'email-verification' => actions\EmailVerificationAction::class,
            'new-email-verification' => actions\NewEmailVerificationAction::class,
            'rules' => actions\AcceptRulesAction::class,
            'enable-two-factor-auth' => actions\EnableTwoFactorAuthAction::class,
            'disable-two-factor-auth' => actions\DisableTwoFactorAuthAction::class,
            'ban' => actions\BanAccountAction::class,
            'pardon' => actions\PardonAccountAction::class,
        ];
    }

    public function actionGet(int $id): array {
        return (new AccountInfo($this->findAccount($id)))->info();
    }

    public function actionGetTwoFactorAuthCredentials(int $id): array {
        return (new TwoFactorAuthInfo($this->findAccount($id)))->getCredentials();
    }

    public function bindActionParams($action, $params): array {
        if (!isset($params['id'])) {
            /** @noinspection NullPointerExceptionInspection */
            $account = Yii::$app->user->getIdentity()->getAccount();
            if ($account !== null) {
                $params['id'] = $account->id;
            }
        }

        return parent::bindActionParams($action, $params);
    }

    private function findAccount(int $id): Account {
        if ($id === 0) {
            /** @noinspection NullPointerExceptionInspection */
            $account = Yii::$app->user->getIdentity()->getAccount();
        } else {
            $account = Account::findOne($id);
        }

        if ($account === null) {
            throw new NotFoundHttpException();
        }

        return $account;
    }

}
