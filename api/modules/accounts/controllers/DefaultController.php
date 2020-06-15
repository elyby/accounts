<?php
declare(strict_types=1);

namespace api\modules\accounts\controllers;

use api\controllers\Controller;
use api\modules\accounts\actions;
use api\modules\accounts\models\AccountInfo;
use api\modules\accounts\models\TwoFactorAuthInfo;
use api\rbac\Permissions as P;
use common\models\Account;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['get'],
                        'roles' => [P::OBTAIN_ACCOUNT_INFO],
                        'roleParams' => $this->createParams([
                            'optionalRules' => true,
                            'allowDeleted' => true,
                        ]),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['username'],
                        'roles' => [P::CHANGE_ACCOUNT_USERNAME],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['password'],
                        'roles' => [P::CHANGE_ACCOUNT_PASSWORD],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['language'],
                        'roles' => [P::CHANGE_ACCOUNT_LANGUAGE],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'email',
                            'email-verification',
                            'new-email-verification',
                        ],
                        'roles' => [P::CHANGE_ACCOUNT_EMAIL],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['rules'],
                        'roles' => [P::ACCEPT_NEW_PROJECT_RULES],
                        'roleParams' => $this->createParams([
                            'optionalRules' => true,
                        ]),
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'get-two-factor-auth-credentials',
                            'enable-two-factor-auth',
                            'disable-two-factor-auth',
                        ],
                        'roles' => [P::MANAGE_TWO_FACTOR_AUTH],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'ban',
                            'pardon',
                        ],
                        'roles' => [P::BLOCK_ACCOUNT],
                        'roleParams' => $this->createParams(),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [P::DELETE_ACCOUNT],
                        'roleParams' => $this->createParams([
                            'optionalRules' => true,
                            'allowDeleted' => true, // This case will be validated by route handler
                        ]),
                    ],
                    [
                        'allow' => true,
                        'actions' => ['restore'],
                        'roles' => [P::RESTORE_ACCOUNT],
                        'roleParams' => $this->createParams([
                            'optionalRules' => true,
                            'allowDeleted' => true,
                        ]),
                    ],
                ],
            ],
            'verb' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['DELETE'],
                    'restore' => ['POST'],
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
            'delete' => actions\DeleteAccountAction::class,
            'restore' => actions\RestoreAccountAction::class,
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

    private function createParams(array $options = []): callable {
        return function() use ($options): array {
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

            return array_merge([
                'accountId' => $id,
            ], $options);
        };
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
