<?php
declare(strict_types=1);

namespace api\models\authentication;

use DateTimeImmutable;
use Lcobucci\JWT\Token;

class AuthenticationResult {

    /**
     * @var Token
     */
    private Token $token;

    /**
     * @var string|null
     */
    private ?string $refreshToken;

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
        /** @var DateTimeImmutable $expiresAt */
        $expiresAt = $this->token->claims()->get('exp');
        $response = [
            'access_token' => $this->token->toString(),
            'expires_in' => $expiresAt->getTimestamp() - (new DateTimeImmutable())->getTimestamp(),
        ];

        $refreshToken = $this->refreshToken;
        if ($refreshToken !== null) {
            $response['refresh_token'] = $refreshToken;
        }

        return $response;
    }

}
