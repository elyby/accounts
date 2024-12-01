<?php
declare(strict_types=1);

namespace api\components\User;

use common\models\Account;
use common\models\OauthSession;
use Exception;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

readonly class LegacyOAuth2Identity implements IdentityInterface {

    /**
     * @param string[] $scopes
     */
    private function __construct(
        private string $accessToken,
        private int $sessionId,
        private array $scopes,
    ) {
    }

    /**
     * @inheritdoc
     * @throws UnauthorizedHttpException
     */
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        $tokenParams = self::findRecordOnLegacyStorage($token);
        if ($tokenParams === null) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        if ($tokenParams['expire_time'] < time()) {
            throw new UnauthorizedHttpException('Token expired');
        }

        return new self($token, $tokenParams['session_id'], $tokenParams['scopes']);
    }

    public function getAccount(): ?Account {
        return $this->getSession()?->account;
    }

    /**
     * @return string[]
     */
    public function getAssignedPermissions(): array {
        return $this->scopes;
    }

    public function getId(): string {
        return $this->accessToken;
    }

    /** @codeCoverageIgnoreStart */
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    /** @codeCoverageIgnoreEnd */
    private static function findRecordOnLegacyStorage(string $accessToken): ?array {
        $record = Yii::$app->redis->get("oauth:access:tokens:{$accessToken}");
        if ($record === null) {
            return null;
        }

        try {
            $data = json_decode($record, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return null;
        }

        $data['scopes'] = (array)Yii::$app->redis->smembers("oauth:access:tokens:{$accessToken}:scopes");

        return $data;
    }

    private function getSession(): ?OauthSession {
        return OauthSession::findOne(['legacy_id' => $this->sessionId]);
    }

}
