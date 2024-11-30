<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\Tokens\TokenReader;
use Carbon\Carbon;
use Carbon\FactoryImmutable;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use DateTimeImmutable;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Validator;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    private ?TokenReader $reader = null;

    private function __construct(
        private readonly Token $token,
    ) {
    }

    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        try {
            $parsedToken = Yii::$app->tokens->parse($token);
        } catch (Exception $e) {
            Yii::error($e);
            throw new UnauthorizedHttpException('Incorrect token');
        }

        if (!Yii::$app->tokens->verify($parsedToken)) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        $now = Carbon::now();
        if ($parsedToken->isExpired($now)) {
            throw new UnauthorizedHttpException('Token expired');
        }

        if (!(new Validator())->validate($parsedToken, new LooseValidAt(FactoryImmutable::getDefaultInstance()))) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        $tokenReader = new TokenReader($parsedToken);
        $accountId = $tokenReader->getAccountId();
        if ($accountId !== null) {
            /** @var DateTimeImmutable $iat */
            $iat = $parsedToken->claims()->get('iat');
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

        return new self($parsedToken);
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

    /** @codeCoverageIgnoreStart */
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    /**
     * @throws NotSupportedException
     */
    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    private static function isRevoked(int $accountId, string $clientId, DateTimeImmutable $iat): bool {
        $session = OauthSession::findOne(['account_id' => $accountId, 'client_id' => $clientId]);
        return $session !== null && $session->revoked_at !== null && $session->revoked_at > $iat->getTimestamp();
    }

    /** @codeCoverageIgnoreEnd */
    private function getReader(): TokenReader {
        if ($this->reader === null) {
            $this->reader = new TokenReader($this->token);
        }

        return $this->reader;
    }

}
