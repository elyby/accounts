<?php
declare(strict_types=1);

namespace common\emails\templates;

use common\components\EmailsRenderer\RendererInterface;
use common\emails\TemplateWithRenderer;

class RegistrationEmail extends TemplateWithRenderer {

    private $params;

    /**
     * @inheritdoc
     */
    public function __construct($to, string $locale, RegistrationEmailParams $params, RendererInterface $renderer) {
        parent::__construct($to, $locale, $renderer);
        $this->params = $params;
    }

    public function getSubject(): string {
        return 'Ely.by Account registration';
    }

    public function getTemplateName(): string {
        return 'register';
    }

    public function getParams(): array {
        return [
            'username' => $this->params->getUsername(),
            'code' => $this->params->getCode(),
            'link' => $this->params->getLink(),
        ];
    }

}
