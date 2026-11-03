<?php
declare(strict_types=1);

namespace common\components\OAuth2\RequestTypes;

use League\OAuth2\Server\RequestTypes\AuthorizationRequest as BaseAuthorizationRequest;

final class AuthorizationRequest extends BaseAuthorizationRequest {

    /**
     * @var non-empty-string|null
     */
    private ?string $nonce = null;

    /**
     * @return non-empty-string|null
     */
    public function getNonce(): ?string {
        return $this->nonce;
    }

    /**
     * @param non-empty-string|null $nonce
     */
    public function setNonce(?string $nonce): void {
        $this->nonce = $nonce;
    }

}
