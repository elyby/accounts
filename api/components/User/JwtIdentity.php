<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\Tokens\TokensFactory;
use Carbon\Carbon;
use common\models\Account;
use Exception;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    /**
     * @var Token
     */
    private $token;

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

        $sub = $token->getClaim('sub', false);
        if ($sub !== false && strpos((string)$sub, TokensFactory::SUB_ACCOUNT_PREFIX) !== 0) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        return new self($token);
    }

    public function getToken(): Token {
        return $this->token;
    }

    public function getAccount(): ?Account {
        $subject = $this->token->getClaim('sub', false);
        if ($subject === false) {
            return null;
        }

        Assert::startsWith($subject, TokensFactory::SUB_ACCOUNT_PREFIX);
        $accountId = (int)mb_substr($subject, mb_strlen(TokensFactory::SUB_ACCOUNT_PREFIX));

        return Account::findOne(['id' => $accountId]);
    }

    public function getAssignedPermissions(): array {
        $scopesClaim = $this->token->getClaim('ely-scopes', false);
        if ($scopesClaim === false) {
            return [];
        }

        return explode(',', $scopesClaim);
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

}
