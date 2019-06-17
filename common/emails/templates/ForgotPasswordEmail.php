<?php
declare(strict_types=1);

namespace common\emails\templates;

use common\emails\TemplateWithRenderer;
use yii\base\InvalidCallException;

class ForgotPasswordEmail extends TemplateWithRenderer {

    /**
     * @var ForgotPasswordParams|null
     */
    private $params;

    public function getSubject(): string {
        return 'Ely.by Account forgot password';
    }

    public function getTemplateName(): string {
        return 'forgotPassword';
    }

    public function setParams(ForgotPasswordParams $params): void {
        $this->params = $params;
    }

    public function getParams(): array {
        if ($this->params === null) {
            throw new InvalidCallException('You need to set params first');
        }

        return [
            'username' => $this->params->getUsername(),
            'code' => $this->params->getCode(),
            'link' => $this->params->getLink(),
        ];
    }

}
