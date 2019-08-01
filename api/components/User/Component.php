<?php
declare(strict_types=1);

namespace api\components\User;

use common\models\Account;
use common\models\AccountSession;
use Exception;
use InvalidArgumentException;
use Yii;
use yii\web\UnauthorizedHttpException;
use yii\web\User as YiiUserComponent;

/**
 * @property AccountSession|null $activeSession
 * @property IdentityInterface|null $identity
 *
 * @method IdentityInterface|null loginByAccessToken($token, $type = null)
 * @method IdentityInterface|null getIdentity($autoRenew = true)
 */
class Component extends YiiUserComponent {

    public const KEEP_MINECRAFT_SESSIONS = 1;
    public const KEEP_SITE_SESSIONS = 2;
    public const KEEP_CURRENT_SESSION = 4;

    public $enableSession = false;

    public $loginUrl = null;

    /**
     * We don't use the standard web authorization mechanism via cookies.
     * Therefore, only one static method findIdentityByAccessToken is used from
     * the whole IdentityInterface interface, which is implemented in the factory.
     * The method only used from loginByAccessToken from base class.
     *
     * @var string
     */
    public $identityClass = IdentityFactory::class;

    public function findIdentityByAccessToken($accessToken): ?IdentityInterface {
        if (empty($accessToken)) {
            return null;
        }

        try {
            return IdentityFactory::findIdentityByAccessToken($accessToken);
        } catch (UnauthorizedHttpException $e) {
            // TODO: if this exception is catched there, how it forms "Token expired" exception?
            // Do nothing. It's okay to catch this.
        } catch (Exception $e) {
            Yii::error($e);
        }

        return null;
    }

    /**
     * The method searches AccountSession model, which one has been used to create current JWT token.
     * null will be returned in case when any of the following situations occurred:
     * - The user isn't authorized
     * - There is no header with a token
     * - Token validation isn't passed and some exception has been thrown
     * - No session key found in the token. This is possible if the user chose not to remember me
     *   or just some old tokens, without the support of saving the used session
     *
     * @return AccountSession|null
     */
    public function getActiveSession(): ?AccountSession {
        if ($this->getIsGuest()) {
            return null;
        }

        $bearer = $this->getBearerToken();
        if ($bearer === null) {
            return null;
        }

        try {
            $token = Yii::$app->tokens->parse($bearer);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        $sessionId = $token->getClaim('jti', false);
        if ($sessionId === false) {
            return null;
        }

        return AccountSession::findOne($sessionId);
    }

    public function terminateSessions(Account $account, int $mode = 0): void {
        $currentSession = null;
        if ($mode & self::KEEP_CURRENT_SESSION) {
            $currentSession = $this->getActiveSession();
        }

        if (!($mode & self::KEEP_SITE_SESSIONS)) {
            foreach ($account->sessions as $session) {
                if ($currentSession === null || $currentSession->id !== $session->id) {
                    $session->delete();
                }
            }
        }

        if (!($mode & self::KEEP_MINECRAFT_SESSIONS)) {
            foreach ($account->minecraftAccessKeys as $minecraftAccessKey) {
                $minecraftAccessKey->delete();
            }
        }
    }

    private function getBearerToken(): ?string {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

}
