<?php
namespace api\components\ReCaptcha;

use yii\base\InvalidConfigException;

class Component extends \yii\base\Component {

    public $public;

    public $secret;

    public function init(): void {
        if ($this->public === null) {
            throw new InvalidConfigException('Public is required');
        }

        if ($this->secret === null) {
            throw new InvalidConfigException('Secret is required');
        }
    }

}
