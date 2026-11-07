<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\Tokens\InvalidTokenException;
use api\components\Tokens\TokenExpiredException;
use api\components\Tokens\TokenReader;
use api\components\Tokens\TokenRevokedException;
use common\models\Account;
use Lcobucci\JWT\UnencryptedToken;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    private ?TokenReader $reader = null;

    private function __construct(
        private readonly UnencryptedToken $token,
    ) {
    }

    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        try {
            $parsedToken = Yii::$app->tokens->verifyAccessToken($token);
        } catch (TokenExpiredException $e) {
            throw new UnauthorizedHttpException('Token expired', previous: $e);
        } catch (TokenRevokedException $e) {
            throw new UnauthorizedHttpException('Token has been revoked', previous: $e);
        } catch (InvalidTokenException $e) {
            throw new UnauthorizedHttpException('Incorrect token', previous: $e);
        }

        return new self($parsedToken);
    }

    public function getToken(): UnencryptedToken {
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

    /** @codeCoverageIgnoreEnd */
    private function getReader(): TokenReader {
        if ($this->reader === null) {
            $this->reader = new TokenReader($this->token);
        }

        return $this->reader;
    }

}
