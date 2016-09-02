<?php
namespace api\modules\session;

use Yii;
use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'session';

    public $defaultRoute = 'session';

    /**
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app) {
        $app->getUrlManager()->addRules([
            // TODO: define normal routes
            //$this->baseDomain . '/' . $this->id . '/auth/<action>' => $this->id . '/authentication/<action>',
        ], false);
    }

    public static function info($message) {
        Yii::info($message, 'session');
    }

    public static function error($message) {
        Yii::info($message, 'session');
    }

}
