<?php
declare(strict_types=1);

namespace common\components\Authentication\Entities;

final readonly class Credentials {

    public function __construct(
        public string $login,
        public string $password,
        public ?string $totp = null,
        public bool $rememberMe = false,
    ) {
    }

}
