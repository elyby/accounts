<?php
declare(strict_types=1);

namespace api\components\Captcha;

trait CaptchaTrait {

    public mixed $captcha = null;

    public mixed $captchaType = null;

    /**
     * @return list<array<int|string, mixed>>
     */
    protected static function getCaptchaValidationRules(): array {
        return [
            ['captcha', CaptchaValidator::class],
            ['captchaType', 'safe'],
        ];
    }

}
