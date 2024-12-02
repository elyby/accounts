<?php
declare(strict_types=1);

namespace common\emails\templates;

use common\emails\Template;
use yii\base\InvalidCallException;

class ChangeEmail extends Template {

    private ?string $key = null;

    public function setKey(string $key): void {
        $this->key = $key;
    }

    public function getSubject(): string {
        return 'Ely.by Account change E-mail confirmation';
    }

    public function getParams(): array {
        if ($this->key === null) {
            throw new InvalidCallException('You need to set key param first');
        }

        return [
            'key' => $this->key,
        ];
    }

    protected function getView(): array {
        return [
            'html' => '@common/emails/views/current-email-confirmation-html',
            'text' => '@common/emails/views/current-email-confirmation-text',
        ];
    }

}
