<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\Tokens\TokenReader;
use Carbon\Carbon;
use common\models\Account;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    /**
     * @var Token
     */
    private $token;

    /**
     * @var TokenReader|null
     */
    private $reader;

    private function __construct(Token $token) {
        $this->token = $token;
    }

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

        if (!$token->validate(new ValidationData($now->getTimestamp()))) {
            throw new UnauthorizedHttpException('Incorrect token');
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
        return (string)$this->token;
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

    // @codeCoverageIgnoreEnd

    private function getReader(): TokenReader {
        if ($this->reader === null) {
            $this->reader = new TokenReader($this->token);
        }

        return $this->reader;
    }

}
