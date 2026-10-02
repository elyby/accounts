<?php
declare(strict_types=1);

namespace api\components\Captcha;

use api\components\Captcha\Providers\ProviderInterface;
use InvalidArgumentException;

final readonly class CaptchaRegistry {

    /**
     * @param array<string, ProviderInterface> $providers provider name => provider
     */
    public function __construct(
        private array $providers,
    ) {
    }

    public function hasProvider(string $name): bool {
        return isset($this->providers[$name]);
    }

    /**
     * @throws InvalidArgumentException if the provider isn't registered
     */
    public function getProvider(string $name): ProviderInterface {
        return $this->providers[$name] ?? throw new InvalidArgumentException(sprintf('The "%s" captcha provider isn\'t registered', $name));
    }

    /**
     * @return array<string, array<string, mixed>> provider name => its public params
     */
    public function getPublicParams(): array {
        return array_map(fn(ProviderInterface $provider): array => $provider->getPublicParams(), $this->providers);
    }

}
