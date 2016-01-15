<?php
namespace api\components\ReCaptcha;

use yii\base\InvalidConfigException;

class Component extends \yii\base\Component {

    public $secret;

    public function init() {
        if ($this->secret === NULL) {
            throw new InvalidConfigException('');
        }
    }

}
