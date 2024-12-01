<?php
declare(strict_types=1);

namespace common\emails\templates;

class RegistrationEmailParams {

    public function __construct(
        private readonly string $username,
        private readonly string $code,
        private readonly string $link,
    ) {
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getLink(): string {
        return $this->link;
    }

}
