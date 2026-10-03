<?php
declare(strict_types=1);

namespace api\components\Captcha\Events;

use Carbon\CarbonInterval;

final class CaptchaResultEvent extends CaptchaEvent {

    /**
     * @param \Carbon\CarbonInterval $duration the duration of unfailed request
     */
    public function __construct(
        string $provider,
        public readonly bool $isValid,
        public readonly CarbonInterval $duration,
        array $config = [],
    ) {
        parent::__construct($provider, $config);
    }

}
