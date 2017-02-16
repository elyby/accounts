<?php
namespace api\modules\internal;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'internal';

    /**
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app) {
        $app->getUrlManager()->addRules([
            '/internal/<controller>/<accountId>/<action>' => "{$this->id}/<controller>/<action>",
        ], false);
    }

}
