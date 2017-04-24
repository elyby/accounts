<?php
namespace common\emails\templates;

use common\emails\TemplateWithRenderer;

class ForgotPasswordEmail extends TemplateWithRenderer {

    private $params;

    /**
     * @inheritdoc
     */
    public function __construct($to, string $locale, ForgotPasswordParams $params) {
        TemplateWithRenderer::__construct($to, $locale);
        $this->params = $params;
    }

    public function getSubject(): string {
        return 'Ely.by Account forgot password';
    }

    public function getTemplateName(): string {
        return 'forgotPassword';
    }

    public function getParams(): array {
        return [
            'username' => $this->params->getUsername(),
            'code' => $this->params->getCode(),
            'link' => $this->params->getLink(),
        ];
    }

}
