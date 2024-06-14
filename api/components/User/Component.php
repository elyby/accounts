<?php
declare(strict_types=1);

namespace api\components\User;

use common\models\Account;
use common\models\AccountSession;
use common\models\OauthClient;
use Webmozart\Assert\Assert;
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

    /**
     * The method searches AccountSession model, which one has been used to create current JWT token.
     * null will be returned in case when any of the following situations occurred:
     * - The user isn't authorized
     * - The user isn't authorized via JWT token
     * - No session key found in the token. This is possible if the user chose not to remember me
     *   or just some old tokens, without the support of saving the used session
     *
     * @return AccountSession|null
     */
    public function getActiveSession(): ?AccountSession {
        if ($this->getIsGuest()) {
            return null;
        }

        /** @var IdentityInterface $identity */
        $identity = $this->getIdentity();
        if (!$identity instanceof JwtIdentity) {
            return null;
        }

        $sessionId = $identity->getToken()->getClaim('jti', false);
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
            /** @var \common\models\OauthSession|null $minecraftSession */
            $minecraftSession = $account->getOauthSessions()
                ->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER])
                ->one();
            if ($minecraftSession !== null) {
                $minecraftSession->revoked_at = time();
                Assert::true($minecraftSession->save());
            }
        }
    }

}
