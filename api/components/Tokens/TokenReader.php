<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Lcobucci\JWT\UnencryptedToken;
use Yii;

final readonly class TokenReader {

    public function __construct(
        private UnencryptedToken $token,
    ) {
    }

    public function getAccountId(): ?int {
        $sub = $this->token->claims()->get('sub', false);
        if ($sub === false) {
            return null;
        }

        if (mb_strpos((string)$sub, TokensFactory::SUB_ACCOUNT_PREFIX) !== 0) {
            return null;
        }

        return (int)mb_substr($sub, mb_strlen(TokensFactory::SUB_ACCOUNT_PREFIX));
    }

    public function getClientId(): ?string {
        return $this->token->claims()->get('client_id', false) ?: null;
    }

    public function getScopes(): ?array {
        $scopes = $this->token->claims()->get('scope', false);
        if ($scopes !== false) {
            return explode(' ', $scopes);
        }

        // Handle legacy tokens, which used "ely-scopes" claim and was delimited with comma
        $scopes = $this->token->claims()->get('ely-scopes', false);
        if ($scopes === false) {
            return null;
        }

        return explode(',', $scopes);
    }

    public function getMinecraftClientToken(): ?string {
        $encodedClientToken = $this->token->claims()->get('ely-client-token', false);
        if ($encodedClientToken === false) {
            return null;
        }

        /**
         * It really might throw an exception, but we have not seen any case of such exception yet
         * @noinspection PhpUnhandledExceptionInspection
         */
        return Yii::$app->tokens->decryptValue($encodedClientToken);
    }

}
