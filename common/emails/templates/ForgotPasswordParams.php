<?php
declare(strict_types=1);

namespace common\emails\templates;

final readonly class ForgotPasswordParams {

    public function __construct(
        public string $username,
        public string $code,
        public string $link,
    ) {
    }

}
