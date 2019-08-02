<?php
declare(strict_types=1);

namespace api\models\authentication;

use Lcobucci\JWT\Token;

class AuthenticationResult {

    /**
     * @var Token
     */
    private $token;

    /**
     * @var string|null
     */
    private $refreshToken;

    public function __construct(Token $token, string $refreshToken = null) {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    public function getToken(): Token {
        return $this->token;
    }

    public function getRefreshToken(): ?string {
        return $this->refreshToken;
    }

    public function formatAsOAuth2Response(): array {
        $response = [
            'access_token' => (string)$this->token,
            'expires_in' => $this->token->getClaim('exp') - time(),
        ];

        $refreshToken = $this->refreshToken;
        if ($refreshToken !== null) {
            $response['refresh_token'] = $refreshToken;
        }

        return $response;
    }

}
