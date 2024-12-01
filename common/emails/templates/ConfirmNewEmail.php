<?php
declare(strict_types=1);

namespace common\emails\templates;

use common\emails\Template;
use yii\base\InvalidCallException;

class ConfirmNewEmail extends Template {

    private ?string $username = null;

    private ?string $key = null;

    public function setUsername(string $username): void {
        $this->username = $username;
    }

    public function setKey(string $key): void {
        $this->key = $key;
    }

    public function getSubject(): string {
        return 'Ely.by Account new E-mail confirmation';
    }

    public function getParams(): array {
        if ($this->username === null || $this->key === null) {
            throw new InvalidCallException('You need to set username and key params first');
        }

        return [
            'username' => $this->username,
            'key' => $this->key,
        ];
    }

    protected function getView(): array {
        return [
            'html' => '@common/emails/views/new-email-confirmation-html',
            'text' => '@common/emails/views/new-email-confirmation-text',
        ];
    }

}
