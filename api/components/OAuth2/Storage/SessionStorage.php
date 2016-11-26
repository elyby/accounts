<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\AuthCodeEntity;
use api\components\OAuth2\Entities\SessionEntity;
use common\models\OauthSession;
use ErrorException;
use League\OAuth2\Server\Entity\AccessTokenEntity as OriginalAccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity as OriginalAuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\SessionInterface;
use yii\db\ActiveQuery;
use yii\db\Exception;

class SessionStorage extends AbstractStorage implements SessionInterface {

    private $cache = [];

    /**
     * @param string $sessionId
     * @return SessionEntity|null
     */
    public function getById($sessionId) {
        return $this->hydrate($this->getSessionModel($sessionId));
    }

    /**
     * @inheritdoc
     */
    public function getByAccessToken(OriginalAccessTokenEntity $accessToken) {
        /** @var OauthSession|null $model */
        $model = OauthSession::find()->innerJoinWith([
            'accessTokens' => function(ActiveQuery $query) use ($accessToken) {
                $query->andWhere(['access_token' => $accessToken->getId()]);
            },
        ])->one();

        return $this->hydrate($model);
    }

    /**
     * @inheritdoc
     */
    public function getByAuthCode(OriginalAuthCodeEntity $authCode) {
        if (!$authCode instanceof AuthCodeEntity) {
            throw new ErrorException('This module assumes that $authCode typeof ' . AuthCodeEntity::class);
        }

        return $this->getById($authCode->getSessionId());
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(OriginalSessionEntity $session) {
        $result = [];
        foreach ($this->getSessionModel($session->getId())->getScopes() as $scope) {
            // TODO: нужно проверить все выданные скоупы на их существование
            $result[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null) {
        $sessionId = OauthSession::find()
            ->select('id')
            ->andWhere([
                'client_id' => $clientId,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
            ])->scalar();

        if ($sessionId === false) {
            $model = new OauthSession();
            $model->client_id = $clientId;
            $model->owner_type = $ownerType;
            $model->owner_id = $ownerId;
            $model->client_redirect_uri = $clientRedirectUri;

            if (!$model->save()) {
                throw new Exception('Cannot save ' . OauthSession::class . ' model.');
            }

            $sessionId = $model->id;
        }

        return $sessionId;
    }

    /**
     * @inheritdoc
     */
    public function associateScope(OriginalSessionEntity $session, ScopeEntity $scope) {
        $this->getSessionModel($session->getId())->getScopes()->add($scope->getId());
    }

    private function getSessionModel(string $sessionId) : OauthSession {
        if (!isset($this->cache[$sessionId])) {
            $this->cache[$sessionId] = OauthSession::findOne($sessionId);
        }

        return $this->cache[$sessionId];
    }

    private function hydrate(OauthSession $sessionModel) {
        $entity = new SessionEntity($this->server);
        $entity->setId($sessionModel->id);
        $entity->setClientId($sessionModel->client_id);
        $entity->setOwner($sessionModel->owner_type, $sessionModel->owner_id);

        return $entity;
    }

}
