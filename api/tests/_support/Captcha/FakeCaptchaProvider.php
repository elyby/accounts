<?php
declare(strict_types=1);

namespace api\tests\_support\Captcha;

use api\components\Captcha\Providers\ProviderInterface;

final readonly class FakeCaptchaProvider implements ProviderInterface {

    public const string VALID = 'validCaptchaToken';

    public function __construct(
        private string $publicKey,
    ) {
    }

    public function getPublicParams(): array {
        return [
            'publicKey' => $this->publicKey,
        ];
    }

    public function verify(string $token, ?string $remoteIp): bool {
        return $token === self::VALID;
    }

}
