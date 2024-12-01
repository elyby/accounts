<?php
declare(strict_types=1);

namespace api\models\authentication;

use DateTimeImmutable;
use Lcobucci\JWT\UnencryptedToken;

final readonly class AuthenticationResult {

    public function __construct(
        private UnencryptedToken $token,
        private ?string $refreshToken = null,
    ) {
    }

    public function getToken(): UnencryptedToken {
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
