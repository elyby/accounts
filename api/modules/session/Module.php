<?php
namespace api\modules\session;

use Yii;

class Module extends \yii\base\Module {

    public $id = 'session';

    public $defaultRoute = 'session';

    public static function info($message): void {
        Yii::info($message, 'session');
    }

    public static function error($message): void {
        Yii::info($message, 'session');
    }

}
