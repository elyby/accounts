<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\ScopeEntity;
use common\models\OauthScope;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ScopeInterface;
use yii\base\ErrorException;

class ScopeStorage extends AbstractStorage implements ScopeInterface {

    /**
     * @inheritdoc
     */
    public function get($scope, $grantType = null, $clientId = null) {
        $query = OauthScope::find();
        if ($grantType === 'authorization_code') {
            $query->onlyPublic()->usersScopes();
        } elseif ($grantType === 'client_credentials') {
            $query->machineScopes();
            $isTrusted = false;
            if ($clientId !== null) {
                $client = $this->server->getClientStorage()->get($clientId);
                if (!$client instanceof ClientEntity) {
                    throw new ErrorException('client storage must return instance of ' . ClientEntity::class);
                }

                $isTrusted = $client->isTrusted();
            }

            if (!$isTrusted) {
                $query->onlyPublic();
            }
        }

        $scopes = $query->all();
        if (!in_array($scope, $scopes, true)) {
            return null;
        }

        $entity = new ScopeEntity($this->server);
        $entity->setId($scope);

        return $entity;
    }

}
