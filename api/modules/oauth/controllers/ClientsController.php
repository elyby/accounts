<?php
namespace api\modules\oauth\controllers;

use api\controllers\Controller;
use api\modules\oauth\exceptions\UnsupportedOauthClientType;
use api\modules\oauth\models\OauthClientForm;
use api\modules\oauth\models\OauthClientFormFactory;
use api\modules\oauth\models\OauthClientTypeForm;
use api\rbac\Permissions as P;
use common\models\Account;
use common\models\OauthClient;
use Webmozart\Assert\Assert;
use Yii;
use yii\filters\AccessControl;
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
                        'roleParams' => function() {
                            return [
                                'clientId' => Yii::$app->request->get('clientId'),
                            ];
                        },
                    ],
                    [
                        'actions' => ['get'],
                        'allow' => true,
                        'permissions' => [P::VIEW_OAUTH_CLIENTS],
                        'roleParams' => function() {
                            return [
                                'clientId' => Yii::$app->request->get('clientId'),
                            ];
                        },
                    ],
                    [
                        'actions' => ['get-per-account'],
                        'allow' => true,
                        'permissions' => [P::VIEW_OAUTH_CLIENTS],
                        'roleParams' => function() {
                            return [
                                'accountId' => Yii::$app->request->get('accountId'),
                            ];
                        },
                    ],
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
        $client->type = $type;
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
        /** @var Account|null $account */
        $account = Account::findOne(['id' => $accountId]);
        if ($account === null) {
            throw new NotFoundHttpException();
        }

        /** @var OauthClient[] $clients */
        $clients = $account->getOauthClients()->orderBy(['created_at' => SORT_ASC])->all();

        return array_map(fn(OauthClient $client): array => $this->formatClient($client), $clients);
    }

    private function formatClient(OauthClient $client): array {
        $result = [
            'clientId' => $client->id,
            'clientSecret' => $client->secret,
            'type' => $client->type,
            'name' => $client->name,
            'websiteUrl' => $client->website_url,
            'createdAt' => $client->created_at,
        ];

        switch ($client->type) {
            case OauthClient::TYPE_APPLICATION:
                $result['description'] = $client->description;
                $result['redirectUri'] = $client->redirect_uri;
                $result['countUsers'] = (int)$client->getSessions()->count();
                break;
            case OauthClient::TYPE_MINECRAFT_SERVER:
                $result['minecraftServerIp'] = $client->minecraft_server_ip;
                break;
        }

        return $result;
    }

    private function createForm(OauthClient $client): OauthClientTypeForm {
        try {
            $model = OauthClientFormFactory::create($client);
        } catch (UnsupportedOauthClientType $e) {
            Yii::warning('Someone tried use ' . $client->type . ' type of oauth form.');
            throw new NotFoundHttpException(null, 0, $e);
        }

        return $model;
    }

    private function findOauthClient(string $clientId): OauthClient {
        /** @var OauthClient|null $client */
        $client = OauthClient::findOne($clientId);
        if ($client === null) {
            throw new NotFoundHttpException();
        }

        return $client;
    }

}
