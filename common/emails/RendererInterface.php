<?php
declare(strict_types=1);

namespace common\emails;

interface RendererInterface {

    public function render(string $templateName, string $locale, array $params = []): string;

}
