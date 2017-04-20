<?php
namespace api\emails\templates;

use api\emails\Template;

class ChangeEmailConfirmCurrentEmail extends Template {

    private $key;

    public function __construct($to, string $key) {
        parent::__construct($to);
        $this->key = $key;
    }

    public function getSubject(): string {
        return 'Ely.by Account change E-mail confirmation';
    }

    /**
     * @return string|array
     */
    protected function getView() {
        return [
            'html' => '@app/mails/current-email-confirmation-html',
            'text' => '@app/mails/current-email-confirmation-text',
        ];
    }

    public function getParams(): array {
        return [
            'key' => $this->key,
        ];
    }

}
