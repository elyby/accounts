<?php
namespace api\controllers;

use api\models\profile\AcceptRulesForm;
use api\models\profile\ChangeEmail\ConfirmNewEmailForm;
use api\models\profile\ChangeEmail\InitStateForm;
use api\models\profile\ChangeEmail\NewEmailForm;
use api\models\profile\ChangeLanguageForm;
use api\models\profile\ChangePasswordForm;
use api\models\profile\ChangeUsernameForm;
use common\helpers\Error as E;
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
                        'actions' => ['current', 'accept-rules'],
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
                            $account = Yii::$app->user->identity;

                            return $account->status > Account::STATUS_REGISTERED
                                && $account->isAgreedWithActualRules();
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
            'accept-rules' => ['POST'],
        ];
    }

    public function actionCurrent() {
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
            'shouldAcceptRules' => !$account->isAgreedWithActualRules(),
        ];
    }

    public function actionChangePassword() {
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
        $account = Yii::$app->user->identity;
        $model = new InitStateForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->sendCurrentEmailConfirmation()) {
            $data = [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];

            if (ArrayHelper::getValue($data['errors'], 'email') === E::RECENTLY_SENT_MESSAGE) {
                $emailActivation = $model->getEmailActivation();
                $data['data'] = [
                    'canRepeatIn' => $emailActivation->canRepeatIn(),
                    'repeatFrequency' => $emailActivation->repeatTimeout,
                ];
            }

            return $data;
        }

        return [
            'success' => true,
        ];
    }

    public function actionChangeEmailSubmitNewEmail() {
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

    public function actionAcceptRules() {
        $account = Yii::$app->user->identity;
        $model = new AcceptRulesForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->agreeWithLatestRules()) {
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
