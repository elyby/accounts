<?php
declare(strict_types=1);

namespace common\components;

use mito\sentry\Component;
use Yii;

class Sentry extends Component {

    public function init() {
        if (!$this->enabled) {
            return;
        }

        if (is_array($this->client) && !isset($this->client['release'])) {
            $this->client['release'] = Yii::$app->version;
        }

        parent::init();
    }

}
