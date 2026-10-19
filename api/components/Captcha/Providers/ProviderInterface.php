<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers;

interface ProviderInterface {

    /**
     * A well known param for traditional captcha providers.
     */
    public const string PUBLIC_KEY_PARAM = 'publicKey';

    /**
     * Provider-specific parameters the client side needs to render the captcha widget.
     * The response is shared between all clients (/api/options is cached by nginx),
     * so the params must not depend on the current request.
     *
     * @return array<string, mixed>
     */
    public function getPublicParams(): array;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException network errors must be thrown as is, so that the caller can retry the request
     * @throws \yii\base\Exception when the provider returns a malformed response
     */
    public function verify(string $token, ?string $remoteIp): bool;

}
