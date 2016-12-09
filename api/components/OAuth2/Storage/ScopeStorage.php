<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\ScopeEntity;
use common\models\OauthScope;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeStorage extends AbstractStorage implements ScopeInterface {

    /**
     * @inheritdoc
     */
    public function get($scope, $grantType = null, $clientId = null) {
        $scopes = $grantType === 'authorization_code' ? OauthScope::getPublicScopes() : OauthScope::getScopes();
        if (!in_array($scope, $scopes, true)) {
            return null;
        }

        $entity = new ScopeEntity($this->server);
        $entity->setId($scope);

        return $entity;
    }

}
