<?php
declare(strict_types=1);

namespace api\components\Captcha\Events;

use yii\base\Event;

class CaptchaEvent extends Event {

    public function __construct(
        public readonly string $provider,
        array $config = [],
    ) {
        parent::__construct($config);
    }

}
