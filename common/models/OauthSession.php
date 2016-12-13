<?php
namespace common\models;

use common\components\Redis\Set;
use Yii;
use yii\base\ErrorException;
use yii\db\ActiveRecord;

/**
 * Поля:
 * @property integer     $id
 * @property string      $owner_type
 * @property string      $owner_id
 * @property string      $client_id
 * @property string      $client_redirect_uri
 *
 * Отношения
 * @property OauthClient $client
 * @property Account     $account
 * @property Set         $scopes
 */
class OauthSession extends ActiveRecord {

    public static function tableName() {
        return '{{%oauth_sessions}}';
    }

    public function getAccessTokens() {
        throw new ErrorException('This method is possible, but not implemented');
    }

    public function getClient() {
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

    public function getAccount() {
        return $this->hasOne(Account::class, ['id' => 'owner_id']);
    }

    public function getScopes() {
        return new Set(static::getDb()->getSchema()->getRawTableName(static::tableName()), $this->id, 'scopes');
    }

    public function beforeDelete() {
        if (!$result = parent::beforeDelete()) {
            return $result;
        }

        $this->getScopes()->delete();
        /** @var \api\components\OAuth2\Storage\RefreshTokenStorage $refreshTokensStorage */
        $refreshTokensStorage = Yii::$app->oauth->getAuthServer()->getRefreshTokenStorage();
        $refreshTokensSet = $refreshTokensStorage->sessionHash($this->id);
        foreach ($refreshTokensSet->members() as $refreshTokenId) {
            $refreshTokensStorage->delete($refreshTokensStorage->get($refreshTokenId));
        }

        $refreshTokensSet->delete();

        return true;
    }

}
