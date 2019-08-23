<?php
namespace common\models;

use common\components\Redis\Set;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property integer     $id
 * @property string      $owner_type contains one of the OauthOwnerType constants
 * @property string|null $owner_id
 * @property string      $client_id
 * @property string      $client_redirect_uri
 * @property integer     $created_at
 *
 * Relations:
 * @property OauthClient $client
 * @property Account     $account
 * @property Set         $scopes
 */
class OauthSession extends ActiveRecord {

    public static function tableName(): string {
        return '{{%oauth_sessions}}';
    }

    public static function find(): OauthSessionQuery {
        return new OauthSessionQuery(static::class);
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function getClient(): ActiveQuery {
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'owner_id']);
    }

    public function getScopes(): Set {
        return new Set(static::getDb()->getSchema()->getRawTableName(static::tableName()), $this->id, 'scopes');
    }

    public function getAccessTokens() {
        throw new NotSupportedException('This method is possible, but not implemented');
    }

    public function beforeDelete(): bool {
        if (!$result = parent::beforeDelete()) {
            return $result;
        }

        $this->clearScopes();
        $this->removeRefreshToken();

        return true;
    }

    public function removeRefreshToken(): void {
        /** @var \api\components\OAuth2\Repositories\RefreshTokenStorage $refreshTokensStorage */
        // TODO: rework
        $refreshTokensStorage = Yii::$app->oauth->getRefreshTokenStorage();
        $refreshTokensSet = $refreshTokensStorage->sessionHash($this->id);
        foreach ($refreshTokensSet->members() as $refreshTokenId) {
            $refreshTokensStorage->delete($refreshTokensStorage->get($refreshTokenId));
        }

        $refreshTokensSet->delete();
    }

    public function clearScopes(): void {
        $this->getScopes()->delete();
    }

}
