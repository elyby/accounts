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
use yii\db\Exception;

class SessionStorage extends AbstractStorage implements SessionInterface {

    /**
     * @param string $sessionId
     * @return SessionEntity|null
     */
    public function getById($sessionId) {
        return $this->hydrate($this->getSessionModel($sessionId));
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

    public function getScopes(OriginalSessionEntity $session) {
        $result = [];
        foreach ($this->getSessionModel($session->getId())->getScopes() as $scope) {
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

    public function associateScope(OriginalSessionEntity $session, ScopeEntity $scope) {
        $this->getSessionModel($session->getId())->getScopes()->add($scope->getId());
    }

    private function getSessionModel(string $sessionId) : OauthSession {
        $session = OauthSession::findOne($sessionId);
        if ($session === null) {
            throw new ErrorException('Cannot find oauth session');
        }

        return $session;
    }

    private function hydrate(OauthSession $sessionModel) {
        $entity = new SessionEntity($this->server);
        $entity->setId($sessionModel->id);
        $entity->setClientId($sessionModel->client_id);
        $entity->setOwner($sessionModel->owner_type, $sessionModel->owner_id);

        return $entity;
    }

}
