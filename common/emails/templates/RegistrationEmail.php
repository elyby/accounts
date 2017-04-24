<?php
namespace common\emails\templates;

use common\emails\TemplateWithRenderer;

class RegistrationEmail extends TemplateWithRenderer {

    private $params;

    /**
     * @inheritdoc
     */
    public function __construct($to, string $locale, RegistrationEmailParams $params) {
        TemplateWithRenderer::__construct($to, $locale);
        $this->params = $params;
    }

    public function getSubject(): string {
        return 'Ely.by Account registration';
    }

    protected function getTemplateName(): string {
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
