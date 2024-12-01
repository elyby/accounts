<?php
declare(strict_types=1);

namespace common\components\EmailsRenderer\Request;

class TemplateRequest {

    public function __construct(
        private readonly string $name,
        private readonly string $locale,
        private readonly array $params,
    ) {
    }

    public function getName(): string {
        return $this->name;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getParams(): array {
        return $this->params;
    }

}
