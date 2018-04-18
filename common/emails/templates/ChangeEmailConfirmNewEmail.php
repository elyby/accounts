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

    public function getParams(): array {
        return [
            'key' => $this->key,
            'username' => $this->username,
        ];
    }

    /**
     * @return string|array
     */
    protected function getView() {
        return [
            'html' => '@common/emails/views/new-email-confirmation-html',
            'text' => '@common/emails/views/new-email-confirmation-text',
        ];
    }

}
