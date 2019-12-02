<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\AuthCodeEntity;
use api\components\OAuth2\Entities\SessionEntity;
use api\exceptions\ThisShouldNotHappenException;
use common\models\OauthSession;
use ErrorException;
use League\OAuth2\Server\Entity\AccessTokenEntity as OriginalAccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity as OriginalAuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\SessionInterface;
use yii\db\Exception;

class SessionStorage extends AbstractStorage implements SessionInterface {

    /**
     * @param string $sessionId
     * @return SessionEntity|null
     */
    public function getById($sessionId): ?SessionEntity {
        $session = $this->getSessionModel($sessionId);
        if ($session === null) {
            return null;
        }

        return $this->hydrate($session);
    }

    public function getByAccessToken(OriginalAccessTokenEntity $accessToken) {
        throw new ErrorException('This method is not implemented and should not be used');
    }

    public function getByAuthCode(OriginalAuthCodeEntity $authCode) {
        if (!$authCode instanceof AuthCodeEntity) {
            throw new ErrorException('This module assumes that $authCode typeof ' . AuthCodeEntity::class);
        }

        return $this->getById($authCode->getSessionId());
    }

    public function getScopes(OriginalSessionEntity $entity) {
        $session = $this->getSessionModel($entity->getId());
        if ($session === null) {
            return [];
        }

        $result = [];
        foreach ($session->getScopes() as $scope) {
            if ($this->server->getScopeStorage()->get($scope) !== null) {
                $result[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
            }
        }

        return $result;
    }

    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null) {
        $sessionId = OauthSession::find()
            ->select('id')
            ->andWhere([
                'client_id' => $clientId,
                'owner_type' => $ownerType,
                'owner_id' => (string)$ownerId, // Casts as a string to make the indexes work, because the varchar field
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

    public function associateScope(OriginalSessionEntity $sessionEntity, ScopeEntity $scopeEntity): void {
        $session = $this->getSessionModel($sessionEntity->getId());
        if ($session === null) {
            throw new ThisShouldNotHappenException('Cannot find oauth session');
        }

        $session->getScopes()->add($scopeEntity->getId());
    }

    private function getSessionModel(string $sessionId): ?OauthSession {
        return OauthSession::findOne(['id' => $sessionId]);
    }

    private function hydrate(OauthSession $sessionModel): SessionEntity {
        $entity = new SessionEntity($this->server);
        $entity->setId($sessionModel->id);
        $entity->setClientId($sessionModel->client_id);
        $entity->setOwner($sessionModel->owner_type, $sessionModel->owner_id);

        return $entity;
    }

}
