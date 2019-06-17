<?php
declare(strict_types=1);

namespace common\tests\_support;

use common\components\EmailsRenderer\Component;

class EmailsRenderer extends Component {

    public function render(string $templateName, string $locale, array $params = []): string {
        return 'template';
    }

}
