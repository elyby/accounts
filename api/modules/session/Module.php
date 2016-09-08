<?php
namespace api\modules\session;

use Yii;

class Module extends \yii\base\Module {

    public $id = 'session';

    public $defaultRoute = 'session';

    public static function info($message) {
        Yii::info($message, 'session');
    }

    public static function error($message) {
        Yii::info($message, 'session');
    }

}
