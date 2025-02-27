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

    public function actionUuidByUsername(string $username, int $at = null): ?array {
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
            return $this->isModernEndpoint() ? $this->contentNotFound("Couldn't find any profile with name {$username}") : $this->noContent();
        }

        return [
            'id' => str_replace('-', '', $account->uuid),
            'name' => $account->username,
        ];
    }

    public function actionUsernamesByUuid(string $uuid): ?array {
        try {
            $uuid = Uuid::fromString($uuid)->toString();
        } catch (\InvalidArgumentException) {
            return $this->illegalArgumentResponse('Invalid uuid format.');
        }

        $account = Account::find()->excludeDeleted()->andWhere(['uuid' => $uuid])->one();
        if ($account === null) {
            return $this->noContent();
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

    public function actionUsernameByUuid(string $uuid): array {
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

    public function actionUuidsByUsernames(): array {
        $usernames = Yii::$app->request->post();
        if (empty($usernames)) {
            return $this->isModernEndpoint() ? $this->constraintViolation('size must be between 1 and 100') : $this->illegalArgumentResponse('Passed array of profile names is an invalid JSON string.');
        }

        $usernames = array_unique($usernames);
        if (count($usernames) > 100) {
            return $this->isModernEndpoint() ? $this->constraintViolation('size must be between 1 and 100') : $this->illegalArgumentResponse('Not more that 100 profile name per call is allowed.');
        }

        foreach ($usernames as $username) {
            if (empty($username) || is_array($username)) {
                return $this->isModernEndpoint() ? $this->constraintViolation('Invalid profile name') : $this->illegalArgumentResponse('profileName can not be null, empty or array key.');
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

    private function isModernEndpoint(): bool {
        $url = Yii::$app->getRequest()->url;
        return str_contains($url, 'mojang/services') || str_contains($url, 'minecraftservices');
    }

    private function noContent(): null {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(204);
        $response->format = Response::FORMAT_RAW;

        return null;
    }

    private function contentNotFound(?string $errorMessage = null): array {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(404);
        $response->format = Response::FORMAT_JSON;
        if ($errorMessage === null) {
            return [
                'path' => Yii::$app->getRequest()->url,
                'error' => 'NOT_FOUND',
                'errorMessage' => 'Not Found',
            ];
        }

        return [
            'path' => Yii::$app->getRequest()->url,
            'errorMessage' => $errorMessage,
        ];
    }

    private function constraintViolation(string $errorMessage): array {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(400);
        $response->format = Response::FORMAT_JSON;

        return [
            'path' => Yii::$app->getRequest()->url,
            'error' => 'CONSTRAINT_VIOLATION',
            'errorMessage' => $errorMessage,
        ];
    }

    private function illegalArgumentResponse(string $errorMessage): array {
        $response = Yii::$app->getResponse();
        $response->setStatusCode(400);
        $response->format = Response::FORMAT_JSON;

        return [
            'error' => 'IllegalArgumentException',
            'errorMessage' => $errorMessage,
        ];
    }

}
