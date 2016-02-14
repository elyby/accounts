<?php
namespace common\components\oauth\Storage\Yii2;

use common\components\oauth\Entity\SessionEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ClientInterface;

class ClientStorage extends AbstractStorage implements ClientInterface {

    /**
     * @inheritdoc
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null) {
        $query = OauthClient::find()
            ->select(['id', 'name', 'secret'])
            ->where([OauthClient::tableName() . '.id' => $clientId]);

        if ($clientSecret !== null) {
            $query->andWhere(['secret' => $clientSecret]);
        }

        if ($redirectUri !== null) {
            $query
                ->addSelect(['redirect_uri'])
                ->andWhere(['redirect_uri' => $redirectUri]);
        }

        $model = $query->asArray()->one();
        if ($model === null) {
            return null;
        }

        $entity = new ClientEntity($this->server);
        $entity->hydrate([
            'id' => $model['id'],
            'name' => $model['name'],
            'secret' => $model['secret'],
        ]);

        if (isset($model['redirect_uri'])) {
            $entity->hydrate([
                'redirectUri' => $model['redirect_uri'],
            ]);
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getBySession(OriginalSessionEntity $session) {
        if (!$session instanceof SessionEntity) {
            throw new \ErrorException('This module assumes that $session typeof ' . SessionEntity::class);
        }

        $model = OauthClient::find()
            ->select(['id', 'name'])
            ->andWhere(['id' => $session->getClientId()])
            ->asArray()
            ->one();

        if ($model === null) {
            return null;
        }

        return (new ClientEntity($this->server))->hydrate($model);
    }

}
