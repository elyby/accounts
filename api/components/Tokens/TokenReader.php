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

        return (int)mb_substr((string)$sub, mb_strlen(TokensFactory::SUB_ACCOUNT_PREFIX));
    }

    public function getClientId(): ?string {
        return $this->token->claims()->get('client_id', false) ?: null;
    }

    /**
     * @return list<non-empty-string>|null
     */
    public function getScopes(): ?array {
        $scopes = $this->token->claims()->get('scope', false);
        if ($scopes !== false) {
            return self::splitScopes((string)$scopes, ' ');
        }

        // Handle legacy tokens, which used "ely-scopes" claim and was delimited with comma
        $scopes = $this->token->claims()->get('ely-scopes', false);
        if ($scopes === false) {
            return null;
        }

        return self::splitScopes((string)$scopes, ',');
    }

    /**
     * @param non-empty-string $scope
     */
    public function hasScope(string $scope): bool {
        return in_array($scope, $this->getScopes() ?? [], true);
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

    /**
     * @param non-empty-string $delimiter
     * @return list<non-empty-string>
     */
    private static function splitScopes(string $scopes, string $delimiter): array {
        return array_values(array_filter(explode($delimiter, $scopes), fn(string $scope): bool => !empty($scope)));
    }

}
