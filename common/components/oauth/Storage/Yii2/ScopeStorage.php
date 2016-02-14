<?php
namespace common\components\oauth\Storage\Yii2;

use common\models\OauthScope;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeStorage extends AbstractStorage implements ScopeInterface {

    /**
     * @inheritdoc
     */
    public function get($scope, $grantType = null, $clientId = null) {
        $row = OauthScope::find()->andWhere(['id' => $scope])->asArray()->one();
        if ($row === null) {
            return null;
        }

        $entity = new ScopeEntity($this->server);
        $entity->hydrate($row);

        return $entity;
    }

}
