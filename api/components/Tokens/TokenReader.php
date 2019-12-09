<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Lcobucci\JWT\Token;
use Yii;

class TokenReader {

    /**
     * @var Token
     */
    private $token;

    public function __construct(Token $token) {
        $this->token = $token;
    }

    public function getAccountId(): ?int {
        $sub = $this->token->getClaim('sub', false);
        if ($sub === false) {
            return null;
        }

        if (mb_strpos((string)$sub, TokensFactory::SUB_ACCOUNT_PREFIX) !== 0) {
            return null;
        }

        return (int)mb_substr($sub, mb_strlen(TokensFactory::SUB_ACCOUNT_PREFIX));
    }

    public function getClientId(): ?string {
        $aud = $this->token->getClaim('aud', false);
        if ($aud === false) {
            return null;
        }

        if (mb_strpos((string)$aud, TokensFactory::AUD_CLIENT_PREFIX) !== 0) {
            return null;
        }

        return mb_substr($aud, mb_strlen(TokensFactory::AUD_CLIENT_PREFIX));
    }

    public function getScopes(): ?array {
        $scopes = $this->token->getClaim('ely-scopes', false);
        if ($scopes === false) {
            return null;
        }

        return explode(',', $scopes);
    }

    public function getMinecraftClientToken(): ?string {
        $encodedClientToken = $this->token->getClaim('ely-client-token', false);
        if ($encodedClientToken === false) {
            return null;
        }

        return Yii::$app->tokens->decryptValue($encodedClientToken);
    }

}
