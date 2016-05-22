<?php
namespace api\controllers;

use api\models\profile\ChangeEmail\ConfirmNewEmailForm;
use api\models\profile\ChangeEmail\InitStateForm;
use api\models\profile\ChangeEmail\NewEmailForm;
use api\models\profile\ChangeLanguageForm;
use api\models\profile\ChangePasswordForm;
use api\models\profile\ChangeUsernameForm;
use common\models\Account;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class AccountsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['current'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'change-password',
                            'change-username',
                            'change-email-initialize',
                            'change-email-submit-new-email',
                            'change-email-confirm-new-email',
                            'change-lang',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function() {
                            /** @var Account $account */
                            $account = Yii::$app->user->identity;
                            return $account->status > Account::STATUS_REGISTERED;
                        },
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'current' => ['GET'],
            'change-password' => ['POST'],
            'change-username' => ['POST'],
            'change-email-initialize' => ['POST'],
            'change-email-submit-new-email' => ['POST'],
            'change-email-confirm-new-email' => ['POST'],
            'change-lang' => ['POST'],
        ];
    }

    public function actionCurrent() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;

        return [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'email' => $account->email,
            'lang' => $account->lang,
            'shouldChangePassword' => $account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY,
            'isActive' => $account->status === Account::STATUS_ACTIVE,
            'passwordChangedAt' => $account->password_changed_at,
            'hasMojangUsernameCollision' => $account->hasMojangUsernameCollision(),
        ];
    }

    public function actionChangePassword() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new ChangePasswordForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->changePassword()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionChangeUsername() {
        $model = new ChangeUsernameForm();
        $model->load(Yii::$app->request->post());
        if (!$model->change()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionChangeEmailInitialize() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new InitStateForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->sendCurrentEmailConfirmation()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionChangeEmailSubmitNewEmail() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new NewEmailForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->sendNewEmailConfirmation()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionChangeEmailConfirmNewEmail() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new ConfirmNewEmailForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->changeEmail()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
            'data' => [
                'email' => $account->email,
            ],
        ];
    }

    public function actionChangeLang() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new ChangeLanguageForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->applyLanguage()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

}
