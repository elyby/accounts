<?php
declare(strict_types=1);

namespace common\components;

use nohnaimer\sentry\Component;
use Yii;

class Sentry extends Component {

    public bool $enabled = true;

    public function init(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (is_array($this->clientOptions) && !isset($this->clientOptions['release'])) {
            $this->clientOptions['release'] = Yii::$app->version;
        }

        parent::init();
    }

}
