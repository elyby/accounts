<?php
namespace common\emails\templates;

class ForgotPasswordParams {

    private $username;

    private $code;

    private $link;

    public function __construct(string $username, string $code, string $link) {
        $this->username = $username;
        $this->code = $code;
        $this->link = $code;
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
