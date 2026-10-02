<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers;

interface ProviderInterface {

    /**
     * Parameters the client side needs to render the captcha widget.
     * Must contain at least the "publicKey".
     *
     * @return array{publicKey: string}&array<string, mixed>
     */
    public function getPublicParams(): array;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException network errors must be thrown as is, so that the caller can retry the request
     * @throws \yii\base\Exception when the provider returns a malformed response
     */
    public function verify(string $token, ?string $remoteIp): bool;

}
