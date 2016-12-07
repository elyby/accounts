<?php
namespace common\components\Sentry;

use Yii;

class Component extends \mito\sentry\Component {

    public $jsNotifier = false;

    public function init() {
        if (is_array($this->client) && !isset($this->client['release'])) {
            $this->client['release'] = Yii::$app->version;
        }

        parent::init();
    }

}
