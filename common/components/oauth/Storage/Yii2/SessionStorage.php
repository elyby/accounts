<?php
namespace common\components\oauth\Storage\Yii2;

use common\components\oauth\Entity\AuthCodeEntity;
use common\components\oauth\Entity\SessionEntity;
use common\models\OauthSession;
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
     * @return OauthSession|null
     */
    private function getSessionModel($sessionId) {
        if (!isset($this->cache[$sessionId])) {
            $this->cache[$sessionId] = OauthSession::findOne($sessionId);
        }

        return $this->cache[$sessionId];
    }

    private function hydrateEntity($sessionModel) {
        if (!$sessionModel instanceof OauthSession) {
            return null;
        }

        return (new SessionEntity($this->server))->hydrate([
            'id' => $sessionModel->id,
            'client_id' => $sessionModel->client_id,
        ])->setOwner($sessionModel->owner_type, $sessionModel->owner_id);
    }

    /**
     * @param string $sessionId
     * @return SessionEntity|null
     */
    public function getSession($sessionId) {
        return $this->hydrateEntity($this->getSessionModel($sessionId));
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

        return $this->hydrateEntity($model);
    }

    /**
     * @inheritdoc
     */
    public function getByAuthCode(OriginalAuthCodeEntity $authCode) {
        if (!$authCode instanceof AuthCodeEntity) {
            throw new \ErrorException('This module assumes that $authCode typeof ' . AuthCodeEntity::class);
        }

        return $this->getSession($authCode->getSessionId());
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
            $model = new OauthSession([
                'client_id' => $clientId,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
            ]);

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

}
