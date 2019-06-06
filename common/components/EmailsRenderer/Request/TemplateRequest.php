<?php
declare(strict_types=1);

namespace common\components\EmailsRenderer\Request;

class TemplateRequest {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $params;

    public function __construct(string $name, string $locale, array $params) {
        $this->name = $name;
        $this->locale = $locale;
        $this->params = $params;
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
