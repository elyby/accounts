<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\controllers\Controller;
use api\modules\oauth\exceptions\UnsupportedOauthClientType;
use api\modules\oauth\models\OauthClientForm;
use api\modules\oauth\models\OauthClientFormFactory;
use api\modules\oauth\models\OauthClientTypeForm;
use api\rbac\Permissions as P;
use common\models\Account;
use common\models\OauthClient;
use common\notifications\OAuthSessionRevokedNotification;
use common\tasks\CreateWebHooksDeliveries;
use Webmozart\Assert\Assert;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class ClientsController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'permissions' => [P::CREATE_OAUTH_CLIENTS],
                    ],
                    [
                        'actions' => ['update', 'delete', 'reset'],
                        'allow' => true,
                        'permissions' => [P::MANAGE_OAUTH_CLIENTS],
                        'roleParams' => fn(): array => [
                            'clientId' => Yii::$app->request->get('clientId'),
                        ],
                    ],
                    [
                        'actions' => ['get'],
                        'allow' => true,
                        'permissions' => [P::VIEW_OAUTH_CLIENTS],
                        'roleParams' => fn(): array => [
                            'clientId' => Yii::$app->request->get('clientId'),
                        ],
                    ],
                    [
                        'actions' => ['get-per-account'],
                        'allow' => true,
                        'permissions' => [P::VIEW_OAUTH_CLIENTS],
                        'roleParams' => fn(): array => [
                            'accountId' => Yii::$app->request->get('accountId'),
                        ],
                    ],
                    [
                        'actions' => ['get-authorized-clients', 'revoke-client'],
                        'allow' => true,
                        'permissions' => [P::MANAGE_OAUTH_SESSIONS],
                        'roleParams' => fn(): array => [
                            'accountId' => Yii::$app->request->get('accountId'),
                        ],
                    ],
                ],
            ],
            'verb' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'get' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'delete' => ['DELETE'],
                    'reset' => ['POST'],
                    'get-per-account' => ['GET'],
                    'get-authorized-clients' => ['GET'],
                    'revoke-client' => ['DELETE'],
                ],
            ],
        ]);
    }

    public function actionGet(string $clientId): array {
        return $this->formatClient($this->findOauthClient($clientId));
    }

    public function actionCreate(string $type): array {
        $account = Yii::$app->user->identity->getAccount();
        Assert::notNull($account === null, 'This form should not to be executed without associated account');

        $client = new OauthClient();
        $client->account_id = $account->id;
        $client->type = $type; // @phpstan-ignore assign.propertyType (this value will be validated in the createForm())
        $requestModel = $this->createForm($client);
        $requestModel->load(Yii::$app->request->post());
        $form = new OauthClientForm($client);
        if (!$form->save($requestModel)) {
            return [
                'success' => false,
                'errors' => $requestModel->getValidationErrors(),
            ];
        }

        return [
            'success' => true,
            'data' => $this->formatClient($client),
        ];
    }

    public function actionUpdate(string $clientId): array {
        $client = $this->findOauthClient($clientId);
        $requestModel = $this->createForm($client);
        $requestModel->load(Yii::$app->request->post());
        $form = new OauthClientForm($client);
        if (!$form->save($requestModel)) {
            return [
                'success' => false,
                'errors' => $requestModel->getValidationErrors(),
            ];
        }

        return [
            'success' => true,
            'data' => $this->formatClient($client),
        ];
    }

    public function actionDelete(string $clientId): array {
        $client = $this->findOauthClient($clientId);
        (new OauthClientForm($client))->delete();

        return [
            'success' => true,
        ];
    }

    public function actionReset(string $clientId, string $regenerateSecret = null): array {
        $client = $this->findOauthClient($clientId);
        $form = new OauthClientForm($client);
        $form->reset($regenerateSecret !== null);

        return [
            'success' => true,
            'data' => $this->formatClient($client),
        ];
    }

    public function actionGetPerAccount(int $accountId): array {
        /** @var OauthClient[] $clients */
        $clients = $this->findAccount($accountId)->getOauthClients()->orderBy(['created_at' => SORT_ASC])->all();

        return array_map(fn(OauthClient $client): array => $this->formatClient($client), $clients);
    }

    public function actionGetAuthorizedClients(int $accountId): array {
        $account = $this->findAccount($accountId);

        $result = [];
        /** @var \common\models\OauthSession[] $oauthSessions */
        $oauthSessions = $account->getOauthSessions()
            ->innerJoinWith(['client c' => function(ActiveQuery $query): void {
                $query->andOnCondition(['c.type' => OauthClient::TYPE_WEB_APPLICATION]);
            }])
            ->andWhere([
                'OR',
                ['revoked_at' => null],
                ['>', 'last_used_at', new Expression('`revoked_at`')],
            ])
            ->all();
        foreach ($oauthSessions as $oauthSession) {
            $client = $oauthSession->client;
            if ($client === null) {
                continue;
            }

            $result[] = [
                'id' => $client->id,
                'name' => $client->name,
                'description' => $client->description,
                'scopes' => $oauthSession->getScopes(),
                'authorizedAt' => $oauthSession->created_at,
                'lastUsedAt' => $oauthSession->last_used_at,
            ];
        }

        return $result;
    }

    public function actionRevokeClient(int $accountId, string $clientId): ?array {
        $account = $this->findAccount($accountId);
        $client = $this->findOauthClient($clientId);

        /** @var \common\models\OauthSession|null $session */
        $session = $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
        if ($session !== null && !$session->isRevoked()) {
            $session->revoked_at = time();
            Assert::true($session->save());

            Yii::$app->queue->push(new CreateWebHooksDeliveries(new OAuthSessionRevokedNotification($session)));
        }

        return ['success' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatClient(OauthClient $client): array {
        $result = [
            'clientId' => $client->id,
            'type' => $client->type,
            'name' => $client->name,
            'websiteUrl' => $client->website_url,
            'createdAt' => $client->created_at,
        ];

        switch ($client->type) {
            case OauthClient::TYPE_WEB_APPLICATION:
                $result['clientSecret'] = $client->secret;
                $result['description'] = $client->description;
                $result['redirectUri'] = $client->redirect_uri;
                $result['countUsers'] = (int)$client->getSessions()->count();
                break;
            case OauthClient::TYPE_DESKTOP_APPLICATION:
                $result['description'] = $client->description;
                $result['countUsers'] = (int)$client->getSessions()->count();
                break;
            case OauthClient::TYPE_MINECRAFT_SERVER:
                $result['clientSecret'] = $client->secret;
                $result['minecraftServerIp'] = $client->minecraft_server_ip;
                break;
        }

        return $result;
    }

    private function createForm(OauthClient $client): OauthClientTypeForm {
        try {
            $model = OauthClientFormFactory::create($client);
        } catch (UnsupportedOauthClientType $e) {
            Yii::warning('Someone tried to use ' . $client->type . ' type of oauth form.');
            throw new NotFoundHttpException(null, 0, $e);
        }

        return $model;
    }

    private function findAccount(int $id): Account {
        /** @var Account|null $account */
        $account = Account::findOne(['id' => $id]);
        if ($account === null) {
            throw new NotFoundHttpException();
        }

        return $account;
    }

    private function findOauthClient(string $clientId): OauthClient {
        /** @var OauthClient|null $client */
        $client = OauthClient::findOne(['id' => $clientId]);
        if ($client === null) {
            throw new NotFoundHttpException();
        }

        return $client;
    }

}
