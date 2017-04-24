<?php
namespace common\emails\templates;

use common\emails\Template;

class ChangeEmailConfirmNewEmail extends Template {

    private $username;

    private $key;

    public function __construct($to, string $username, string $key) {
        parent::__construct($to);
        $this->username = $username;
        $this->key = $key;
    }

    public function getSubject(): string {
        return 'Ely.by Account new E-mail confirmation';
    }

    /**
     * @return string|array
     */
    protected function getView() {
        return [
            'html' => '@api/mails/new-email-confirmation-html',
            'text' => '@api/mails/new-email-confirmation-text',
        ];
    }

    public function getParams(): array {
        return [
            'key' => $this->key,
            'username' => $this->username,
        ];
    }

}
