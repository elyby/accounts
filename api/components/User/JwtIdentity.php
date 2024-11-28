<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\Tokens\TokenReader;
use Carbon\Carbon;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use DateTimeImmutable;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface as Clock;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    /**
     * @var Token
     */
    private Token $token;

    /**
     * @var TokenReader|null
     */
    private ?TokenReader $reader = null;

    private function __construct(Token $token) {
        $this->token = $token;
    }

    /** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
    public static function findIdentityByAccessToken($rawToken, $type = null): IdentityInterface {
        try {
            $token = Yii::$app->tokens->parse($rawToken);
        } catch (Exception $e) {
            Yii::error($e);
            throw new UnauthorizedHttpException('Incorrect token');
        }

        if (!Yii::$app->tokens->verify($token)) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        $now = Carbon::now();
        if ($token->isExpired($now)) {
            throw new UnauthorizedHttpException('Token expired');
        }

        // very cool how Carbon doesn't even HAVE A REAL CLOCK (it's just null)
        // PHP is fucking hell.
        if (!(new Validator())->validate($token, new LooseValidAt($now->getClock() ?? new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        }))) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        $tokenReader = new TokenReader($token);
        $accountId = $tokenReader->getAccountId();
        if ($accountId !== null) {
            $iat = $token->claims()->get('iat');
            if ($tokenReader->getMinecraftClientToken() !== null
             && self::isRevoked($accountId, OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER, $iat)
            ) {
                throw new UnauthorizedHttpException('Token has been revoked');
            }

            if ($tokenReader->getClientId() !== null
             && self::isRevoked($accountId, $tokenReader->getClientId(), $iat)
            ) {
                throw new UnauthorizedHttpException('Token has been revoked');
            }
        }

        return new self($token);
    }

    public function getToken(): Token {
        return $this->token;
    }

    public function getAccount(): ?Account {
        return Account::findOne(['id' => $this->getReader()->getAccountId()]);
    }

    public function getAssignedPermissions(): array {
        return $this->getReader()->getScopes() ?? [];
    }

    public function getId(): string {
        return $this->token->toString();
    }

    // @codeCoverageIgnoreStart
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    private static function isRevoked(int $accountId, string $clientId, int $iat): bool {
        $session = OauthSession::findOne(['account_id' => $accountId, 'client_id' => $clientId]);
        return $session !== null && $session->revoked_at !== null && $session->revoked_at > $iat;
    }

    // @codeCoverageIgnoreEnd

    private function getReader(): TokenReader {
        if ($this->reader === null) {
            $this->reader = new TokenReader($this->token);
        }

        return $this->reader;
    }

}
