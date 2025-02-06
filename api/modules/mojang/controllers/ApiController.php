<?php
declare(strict_types=1);

namespace api\modules\mojang\controllers;

use api\controllers\Controller;
use common\models\Account;
use common\models\UsernameHistory;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;
use yii\web\Response;

class ApiController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => new UnsetArrayValue(),
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'actionUuidsByUsernames' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionUuidByUsername(string $username, int $at = null): Response|array {
        if ($at !== null) {
            /** @var UsernameHistory|null $record */
            $record = UsernameHistory::find()
                ->andWhere(['username' => $username])
                ->orderBy(['applied_in' => SORT_DESC])
                ->andWhere(['<=', 'applied_in', $at])
                ->one();

            // The query above simply finds the latest case of usage, without taking into account the fact
            // that the nickname may have been changed since then. Therefore, we additionally check
            // that the nickname is in some period (i.e. there is a subsequent entry) or that the last user
            // who used the nickname has not changed it to something else
            $account = null;
            if ($record !== null) {
                if ($record->account->username === $record->username || $record->findNextOwnerUsername($at) !== null) {
                    $account = $record->account;
                }
            }
        } else {
            /** @var Account|null $account */
            $account = Account::findOne(['username' => $username]);
        }

        if ($account === null || $account->status === Account::STATUS_DELETED) {
            return $this->contentNotFound("Couldn't find any profile with name {$username}");
        }

        return [
            'id' => str_replace('-', '', $account->uuid),
            'name' => $account->username,
        ];
    }

    public function actionUsernameByUuid(string $uuid): Response|array {
        try {
            $uuid = Uuid::fromString($uuid)->toString();
        } catch (\InvalidArgumentException) {
            return $this->constraintViolation("Invalid UUID string: {$uuid}");
        }

        /** @var Account|null $account */
        $account = Account::findOne(['uuid' => $uuid]);

        if ($account === null || $account->status === Account::STATUS_DELETED) {
            return $this->contentNotFound();
        }

        return [
            'id' => str_replace('-', '', $account->uuid),
            'name' => $account->username,
        ];
    }

    public function actionUsernamesByUuid(string $uuid): Response|array {
        try {
            $uuid = Uuid::fromString($uuid)->toString();
        } catch (\InvalidArgumentException) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(400);
            $response->format = Response::FORMAT_JSON;
            $response->data = [
                'error' => 'IllegalArgumentException',
                'errorMessage' => 'Invalid uuid format.',
            ];

            return $response;
        }

        $account = Account::find()->excludeDeleted()->andWhere(['uuid' => $uuid])->one();
        if ($account === null) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(204);
            $response->format = Response::FORMAT_RAW;
            $response->content = '';

            return $response;
        }

        /** @var UsernameHistory[] $usernameHistory */
        $usernameHistory = $account->getUsernameHistory()
            ->orderBy(['applied_in' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($usernameHistory as $record) {
            $data[] = [
                'name' => $record->username,
                'changedToAt' => $record->applied_in * 1000,
            ];
        }

        // The first element shouldn't have time when it was applied.
        // Although we know this information in fact. But Mojang probably doesn't.
        unset($data[0]['changedToAt']);

        return $data;
    }

    public function actionUuidsByUsernames(): Response|array {
        $usernames = Yii::$app->request->post();
        if (empty($usernames)) {
            return $this->constraintViolation('size must be between 1 and 100');
        }

        $usernames = array_unique($usernames);
        if (count($usernames) > 100) {
            return $this->constraintViolation('size must be between 1 and 100');
        }

        foreach ($usernames as $username) {
            if (empty($username) || is_array($username)) {
                return $this->constraintViolation('Invalid profile name');
            }
        }

        /** @var Account[] $accounts */
        $accounts = Account::find()
            ->andWhere(['username' => $usernames])
            ->excludeDeleted()
            ->orderBy(['username' => $usernames])
            ->limit(count($usernames))
            ->all();

        $responseData = [];
        foreach ($accounts as $account) {
            $responseData[] = [
                'id' => str_replace('-', '', $account->uuid),
                'name' => $account->username,
            ];
        }

        return $responseData;
    }

    private function contentNotFound(string|null $errorMessage = null): Response {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(404);
        $response->format = Response::FORMAT_JSON;
        if ($errorMessage === null) {
            $response->data = [
                'path' => Yii::$app->getRequest()->url,
                'error' => 'NOT_FOUND',
                'errorMessage' => 'Not Found',
            ];
        } else {
            $response->data = [
                'path' => Yii::$app->getRequest()->url,
                'errorMessage' => $errorMessage,
            ];
        }

        return $response;
    }

    private function constraintViolation(string $errorMessage): Response {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(400);
        $response->format = Response::FORMAT_JSON;
        $response->data = [
            'path' => Yii::$app->getRequest()->url,
            'error' => 'CONSTRAINT_VIOLATION',
            'errorMessage' => $errorMessage,
        ];

        return $response;
    }

}
